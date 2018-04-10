<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\Webhosting\Application\Account;

use ParkManager\Component\SharedKernel\RootEntityOwner;
use ParkManager\Module\Webhosting\Domain\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Domain\DomainName;
use ParkManager\Module\Webhosting\Domain\Package\Capabilities;
use ParkManager\Module\Webhosting\Domain\Package\WebhostingPackageId;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RegisterWebhostingAccount
{
    private $id;
    private $domainName;
    private $owner;
    private $package;
    private $capabilities;

    private function __construct(string $id, string $owner, DomainName $domainName, ?string $package, ?Capabilities $capabilities)
    {
        $this->id = WebhostingAccountId::fromString($id);
        $this->domainName = $domainName;
        $this->capabilities = $capabilities;
        $this->owner = RootEntityOwner::fromString($owner);

        if (null !== $package) {
            $this->package = WebhostingPackageId::fromString($package);
        }
    }

    public static function withPackage(string $id, DomainName $domainName, string $owner, string $packageId): self
    {
        return new self($id, $owner, $domainName, $packageId, null);
    }

    public static function withCustomCapabilities(string $id, DomainName $domainName, string $owner, Capabilities $capabilities): self
    {
        return new self($id, $owner, $domainName, null, $capabilities);
    }

    public function id(): WebhostingAccountId
    {
        return $this->id;
    }

    public function owner(): RootEntityOwner
    {
        return $this->owner;
    }

    public function customCapabilities(): ?Capabilities
    {
        return $this->capabilities;
    }

    public function package(): ?WebhostingPackageId
    {
        return $this->package;
    }

    public function domainName(): DomainName
    {
        return $this->domainName;
    }
}
