<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\MalformedEmailAddress;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * @internal
 */
final class EmailAddressTest extends TestCase
{
    /** @test */
    public function its_constructable(): void
    {
        $value = new EmailAddress('info@example.com');

        self::assertEquals('info@example.com', $value->address);
        self::assertEquals('info@example.com', $value->toString());
        self::assertEquals('info@example.com', $value->canonical);
        self::assertEquals('info', $value->local);
        self::assertEquals('example.com', $value->domain);
        self::assertEquals('', $value->name);
        self::assertEquals('', $value->label);

        $value->validate();
    }

    /** @test */
    public function its_constructable_with_double_at_sign(): void
    {
        $value = new EmailAddress('"info@hello"@example.com');

        self::assertEquals('"info@hello"@example.com', $value->address);
        self::assertEquals('"info@hello"@example.com', $value->toString());
        self::assertEquals('"info@hello"@example.com', $value->canonical);
        self::assertEquals('"info@hello"', $value->local);
        self::assertEquals('example.com', $value->domain);
        self::assertEquals('', $value->name);
        self::assertEquals('', $value->label);

        $value->validate();
    }

    /** @test */
    public function its_constructable_as_pattern(): void
    {
        $value = new EmailAddress('*@example.com');

        self::assertEquals('*@example.com', $value->address);
        self::assertEquals('*@example.com', $value->toString());
        self::assertEquals('*@example.com', $value->canonical);
        self::assertEquals('*', $value->local);
        self::assertEquals('example.com', $value->domain);
        self::assertEquals('', $value->name);
        self::assertEquals('', $value->label);

        $value->validate();
    }

    /** @test */
    public function its_constructable_with_name(): void
    {
        $value = new EmailAddress('info@example.com', 'Janet Doe');

        self::assertEquals('info@example.com', $value->address);
        self::assertEquals('info@example.com', $value->canonical);
        self::assertEquals('Janet Doe', $value->name);
        self::assertEquals('', $value->label);
    }

    /** @test */
    public function it_canonicalizes_the_address(): void
    {
        $value = new EmailAddress('infO@EXAMPLE.com');

        self::assertEquals('infO@EXAMPLE.com', $value->address);
        self::assertEquals('info@example.com', $value->canonical);
        self::assertEquals('info', $value->local);
        self::assertEquals('example.com', $value->domain);
        self::assertEquals('', $value->name);
        self::assertEquals('', $value->label);
    }

    /** @test */
    public function it_canonicalizes_the_address_with_idn(): void
    {
        $value = new EmailAddress('info@xn--tst-qla.de');

        // Note. Original value is not transformed as some IDN TLDs
        // are not supported natively (Emoji for example).
        self::assertEquals('info@xn--tst-qla.de', $value->address);
        self::assertEquals('info@täst.de', $value->canonical);
        self::assertEquals('info', $value->local);
        self::assertEquals('täst.de', $value->domain);
        self::assertEquals('', $value->name);
        self::assertEquals('', $value->label);
    }

    /** @test */
    public function it_extracts_the_label(): void
    {
        $value = new EmailAddress('info+hello@example.com');

        self::assertEquals('info+hello@example.com', $value->address);
        self::assertEquals('info@example.com', $value->canonical);
        self::assertEquals('info', $value->local);
        self::assertEquals('example.com', $value->domain);
        self::assertEquals('', $value->name);
        self::assertEquals('hello', $value->label);
    }

    /** @test */
    public function it_validates_basic_formatting(): void
    {
        $this->expectExceptionObject(MalformedEmailAddress::missingAtSign('info?example.com'));

        new EmailAddress('info?example.com');
    }

    /** @test */
    public function it_validates_advanced_formatting(): void
    {
        $this->expectException(RfcComplianceException::class);

        $address = new EmailAddress('"=--WAT--@"=@example.com');
        $address->validate();
    }

    /** @test */
    public function it_validates_idn_format(): void
    {
        $this->expectExceptionObject(MalformedEmailAddress::idnError('ok@xn--wat.de', IDNA_ERROR_INVALID_ACE_LABEL));

        new EmailAddress('ok@xn--wat.de');
    }

    /** @test */
    public function it_validates_pattern_multi_wildcard(): void
    {
        $this->expectExceptionObject(MalformedEmailAddress::patternMultipleWildcards('*info*@example.com'));

        new EmailAddress('*info*@example.com');
    }

    /** @test */
    public function it_validates_pattern_in_label(): void
    {
        $this->expectExceptionObject(MalformedEmailAddress::patternWildcardInLabel('info+labeled*@example.com'));

        new EmailAddress('info+labeled*@example.com');
    }
}
