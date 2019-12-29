<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\UseCase\Administrator;

use ParkManager\Bundle\CoreBundle\Model\Administrator\Administrator;
use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorId;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Event\AdministratorPasswordWasChanged;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Event\AdministratorWasRegistered;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Exception\AdministratorEmailAddressAlreadyInUse;
use ParkManager\Bundle\CoreBundle\Model\EmailAddress;
use ParkManager\Bundle\CoreBundle\Test\Model\Repository\AdministratorRepositoryMock;
use ParkManager\Bundle\CoreBundle\UseCase\Administrator\RegisterAdministrator;
use ParkManager\Bundle\CoreBundle\UseCase\Administrator\RegisterAdministratorHandler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RegisterAdministratorHandlerTest extends TestCase
{
    private const ID_NEW = '01dd5964-5426-11e7-be03-acbc32b58315';
    private const ID_EXISTING = 'a0816f44-6545-11e7-a234-acbc32b58315';

    /** @test */
    public function handle_registration_of_new_administrator(): void
    {
        $repo = new AdministratorRepositoryMock();
        $handler = new RegisterAdministratorHandler($repo);

        $command = new RegisterAdministrator(self::ID_NEW, 'John@example.com', 'My', 'my-password');
        $handler($command);

        $repo->assertHasEntityWithEvents(
            self::ID_NEW,
            [
                new AdministratorWasRegistered($command->id, $command->email, $command->displayName),
                new AdministratorPasswordWasChanged($command->id, $command->password),
            ]
        );
    }

    /** @test */
    public function handle_registration_without_password(): void
    {
        $repo = new AdministratorRepositoryMock();
        $handler = new RegisterAdministratorHandler($repo);

        $command = new RegisterAdministrator(self::ID_NEW, 'John@example.com', 'My', null);
        $handler($command);

        $repo->assertHasEntityWithEvents(
            self::ID_NEW,
            [
                new AdministratorWasRegistered($command->id, $command->email, $command->displayName),
            ]
        );
    }

    /** @test */
    public function handle_registration_of_new_user_with_already_existing_email(): void
    {
        $repo = new AdministratorRepositoryMock(
            [
                Administrator::register(
                    AdministratorId::fromString(self::ID_EXISTING),
                    new EmailAddress('John@example.com'),
                    'Jane'
                ),
            ]
        );
        $handler = new RegisterAdministratorHandler($repo);

        $this->expectException(AdministratorEmailAddressAlreadyInUse::class);

        $handler(new RegisterAdministrator(self::ID_NEW, 'John@example.com', 'My', null));
    }
}
