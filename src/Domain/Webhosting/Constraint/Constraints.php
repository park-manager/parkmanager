<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Constraint;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\ByteSize;

/**
 * @ORM\Embeddable
 */
class Constraints
{
    /**
     * READ-ONLY: Traffic in GB.
     *
     * @ORM\Column(type="integer")
     */
    public int $monthlyTraffic = -1;

    /**
     * READ-ONLY: Total Storage size to allow allocating.
     *
     * Note: This effects all storage related constraints, if `mailboxStorageSize`
     * is set to 'Inf' the actual limit is *this* value.
     *
     * @ORM\Column(type="byte_size")
     */
    public ByteSize $storageSize;

    /**
     * READ-ONLY: Email constraints.
     *
     * @ORM\Embedded(class=EmailConstraints::class, columnPrefix="email_")
     */
    public EmailConstraints $email;

    /**
     * READ-ONLY: Database constraints.
     *
     * @ORM\Embedded(class=DBConstraints::class, columnPrefix="database_")
     */
    public DBConstraints $database;

    /**
     * READ-ONLY: Change Tracking.
     *
     * [name] => old-value
     *
     * @var array<string,mixed>
     */
    public array $changes = [];

    public function __construct(array $fields = [])
    {
        $this->storageSize = ByteSize::inf();
        $this->email = new EmailConstraints();
        $this->database = new DBConstraints();

        foreach ($fields as $name => $value) {
            if (\property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    public function __clone()
    {
        $this->email = clone $this->email;
        $this->database = clone $this->database;
    }

    public function equals(self $other): bool
    {
        if (! $this->email->equals($other->email)) {
            return false;
        }

        if (! $this->database->equals($other->database)) {
            return false;
        }

        if (! $this->storageSize->equals($other->storageSize)) {
            return false;
        }

        if ($this->monthlyTraffic !== $other->monthlyTraffic) {
            return false;
        }

        return true;
    }

    public function setEmail(EmailConstraints $email): self
    {
        if ($this->email->equals($email)) {
            return $this;
        }

        $self = clone $this;
        $self->email = $email;
        $self->changes['email'] = $this->email;

        return $self;
    }

    public function setDatabase(DBConstraints $db): self
    {
        if ($this->database->equals($db)) {
            return $this;
        }

        $self = clone $this;
        $self->database = $db;
        $self->changes['database'] = $this->database;

        return $self;
    }

    public function setStorageSize(ByteSize $value): self
    {
        if ($this->storageSize->equals($value)) {
            return $this;
        }

        $self = clone $this;
        $self->storageSize = $value;
        $self->changes['storageSize'] = $this->storageSize;

        return $self;
    }

    public function setMonthlyTraffic(int $value): self
    {
        if ($this->monthlyTraffic === $value) {
            return $this;
        }

        $self = clone $this;
        $self->monthlyTraffic = $value;
        $self->changes['monthlyTraffic'] = $this->monthlyTraffic;

        return $self;
    }
}
