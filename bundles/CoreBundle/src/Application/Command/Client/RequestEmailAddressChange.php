<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Application\Command\Client;

use ParkManager\Bundle\CoreBundle\Domain\Client\ClientId;
use ParkManager\Bundle\CoreBundle\Domain\Shared\EmailAddress;

final class RequestEmailAddressChange
{
    private $id;
    private $email;

    public function __construct(string $id, string $email)
    {
        $this->id    = ClientId::fromString($id);
        $this->email = new EmailAddress($email);
    }

    public function id(): ClientId
    {
        return $this->id;
    }

    public function email(): EmailAddress
    {
        return $this->email;
    }
}