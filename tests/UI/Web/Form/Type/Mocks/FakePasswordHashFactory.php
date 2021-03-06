<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type\Mocks;

use ParkManager\Infrastructure\Security\SecurityUser;
use RuntimeException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/** @internal */
final class FakePasswordHashFactory implements EncoderFactoryInterface
{
    private object $encoder;

    private string $userClass;

    public function __construct()
    {
        $this->userClass = SecurityUser::class;
        $this->encoder = new class() implements PasswordEncoderInterface {
            public function encodePassword($raw, $salt): string
            {
                return 'encoded(' . $raw . ')';
            }

            public function isPasswordValid($encoded, $raw, $salt): bool
            {
                return false;
            }

            public function needsRehash(string $encoded): bool
            {
                return false;
            }
        };
    }

    public function getEncoder($user): PasswordEncoderInterface
    {
        if ($user !== $this->userClass) {
            throw new RuntimeException('Nope, that is not the right user.');
        }

        return $this->encoder;
    }
}
