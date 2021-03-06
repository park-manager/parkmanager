<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Administrator;

use ParkManager\Application\Command\Administrator\RegisterAdministrator;
use ParkManager\Application\Command\Administrator\RegisterAdministratorHandler;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
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
        $repo = new UserRepositoryMock();
        $handler = new RegisterAdministratorHandler($repo);

        $command = RegisterAdministrator::with(self::ID_NEW, 'John@example.com', 'My name', 'my-password');
        $handler($command);

        $repo->assertHasEntity(self::ID_NEW, static function (User $user): void {
            self::assertEquals(UserId::fromString(self::ID_NEW), $user->id);
            self::assertEquals(new EmailAddress('John@example.com'), $user->email);
            self::assertEquals('My name', $user->displayName);
            self::assertEquals('my-password', $user->password);
        });
    }

    /** @test */
    public function handle_registration_of_new_user_with_already_existing_email(): void
    {
        $repo = new UserRepositoryMock(
            [
                User::registerAdmin(
                    UserId::fromString(self::ID_EXISTING),
                    new EmailAddress('John@example.com'),
                    'Jane'
                ),
            ]
        );
        $handler = new RegisterAdministratorHandler($repo);

        $this->expectException(EmailAddressAlreadyInUse::class);

        $handler(RegisterAdministrator::with(self::ID_NEW, 'John@example.com', 'My', null));
    }
}
