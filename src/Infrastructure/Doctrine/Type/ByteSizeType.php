<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;
use InvalidArgumentException;
use ParkManager\Domain\ByteSize;

final class ByteSizeType extends IntegerType
{
    public function getName(): string
    {
        return 'byte_size';
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getBigIntTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ByteSize
    {
        return $value === null ? null : new ByteSize($value, 'byte');
    }

    /**
     * @param ByteSize|null $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof ByteSize) {
            throw new InvalidArgumentException('Expected ByteSize instance.');
        }

        return parent::convertToDatabaseValue($value->value, $platform);
    }
}
