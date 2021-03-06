<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\TLS;

use Doctrine\ORM\Mapping as ORM;

/**
 * Holds a TLS Certificate (immutable).
 *
 * As a cert or key may be used multiple times for different hosts (SAN),
 * we don't store these with the SubDomain information itself but instead
 * store each key/cert only once (for storage sufficiency).
 *
 * The SubDomain references these values using their unique hashes.
 *
 * @ORM\Entity
 * @ORM\Table(name="host_tls_cert")
 */
class Certificate
{
    use x509Data {
        __construct as construct;
    }

    /**
     * @ORM\Column(type="binary")
     *
     * @var resource|string
     */
    private $privateKey;

    private ?string $privateKeyString;

    /**
     * @param array<string, string> $rawFields
     */
    public function __construct(string $contents, string $privateKey, array $rawFields, ?CA $ca = null)
    {
        $this->construct($contents, $rawFields, $ca);

        $this->privateKey = $privateKey;
        $this->privateKeyString = $privateKey;

        if ($ca === null && ! $this->isSelfSigned()) {
            throw new \InvalidArgumentException('A CA must be provided when the Certificate is not self-signed.');
        }
    }

    public function isSelfSigned(): bool
    {
        return $this->getIssuer() === $this->rawFields['subject'];
    }

    public function getDomain(): string
    {
        return $this->rawFields['subject']['commonName'];
    }

    public function supportsDomain(string $domain): bool
    {
        foreach ($this->getDomains() as $value) {
            if (\preg_match('#^' . \str_replace(['.', '*'], ['\.', '[^.]*'], $value) . '$#', $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function getDomains(): array
    {
        return $this->rawFields['_domains'];
    }

    /**
     * @return array<int, string>
     */
    public function getAdditionalDomains(): array
    {
        return $this->rawFields['altNames'] ?? [];
    }

    /**
     * Gets the private-key in storage-encrypted format.
     */
    public function getPrivateKey(): string
    {
        if (! isset($this->privateKeyString)) {
            $this->privateKeyString = \stream_get_contents($this->privateKey);
        }

        return $this->privateKeyString;
    }
}
