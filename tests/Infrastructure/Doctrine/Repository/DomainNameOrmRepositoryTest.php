<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\DomainName\Exception\CannotRemovePrimaryDomainName;
use ParkManager\Domain\DomainName\Exception\DomainNameNotFound;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Doctrine\Repository\DomainNameOrmRepository;
use ParkManager\Tests\Infrastructure\Doctrine\EntityRepositoryTestCase;

/**
 * @internal
 *
 * @group functional
 */
final class DomainNameOrmRepositoryTest extends EntityRepositoryTestCase
{
    private const OWNER_ID1 = '3f8da982-a528-11e7-a2da-acbc32b58315';
    private const OWNER_ID2 = 'bf31144d-dc3b-40be-93e1-1128684f6ee1';

    private const SPACE_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const SPACE_ID2 = '47f6db14-a69c-11e7-be13-acbc32b58316';
    private const SPACE_NOOP = '30b26ae0-a6b5-11e7-b978-acbc32b58315';

    private DomainNameOrmRepository $repository;

    private Space $space1;
    private Space $space2;
    private Space $space3;

    private DomainNameId $id1;
    private DomainNameId $id2;
    private DomainNameId $id3;
    private DomainNameId $id4;
    private DomainNameId $id5;
    private DomainNameId $id6;

    protected function setUp(): void
    {
        parent::setUp();

        $user1 = User::register(UserId::fromString(self::OWNER_ID1), new EmailAddress('John@mustash.com'), 'John');
        $user2 = User::register(UserId::fromString(self::OWNER_ID2), new EmailAddress('Jane@mustash.com'), 'Jane');

        $this->space1 = Space::registerWithCustomConstraints(
            SpaceId::fromString(self::SPACE_ID1),
            $user1,
            new Constraints()
        );

        $this->space2 = Space::registerWithCustomConstraints(
            SpaceId::fromString(self::SPACE_ID2),
            $user2,
            new Constraints()
        );

        $this->space3 = Space::registerWithCustomConstraints(
            SpaceId::create(),
            null,
            new Constraints()
        );

        $em = $this->getEntityManager();
        $em->transactional(function (EntityManagerInterface $em) use ($user1, $user2): void {
            $em->persist($user1);
            $em->persist($user2);
            $em->persist($this->space1);
            $em->persist($this->space2);
            $em->persist($this->space3);
        });

        $webhostingDomainName1 = DomainName::registerForSpace(DomainNameId::create(), $this->space1, new DomainNamePair('example', 'com'));
        $this->id1 = $webhostingDomainName1->getId();

        $webhostingDomainName2 = DomainName::registerForSpace(DomainNameId::create(), $this->space2, new DomainNamePair('example', 'net'));
        $this->id2 = $webhostingDomainName2->getId();

        $webhostingDomainName3 = DomainName::registerSecondaryForSpace(DomainNameId::create(), $this->space2, new DomainNamePair('example', 'co.uk'));
        $this->id3 = $webhostingDomainName3->getId();

        $webhostingDomainName4 = DomainName::register(DomainNameId::create(), new DomainNamePair('example', 'nl'), null);
        $this->id4 = $webhostingDomainName4->getId();

        $webhostingDomainName5 = DomainName::register(DomainNameId::create(), new DomainNamePair('example', 'nu'), $user1);
        $this->id5 = $webhostingDomainName5->getId();

        $webhostingDomainName6 = DomainName::registerForSpace(DomainNameId::create(), $this->space3, new DomainNamePair('example', 'nu'));
        $this->id6 = $webhostingDomainName6->getId();

        $this->repository = new DomainNameOrmRepository($em);
        $this->repository->save($webhostingDomainName1);
        $this->repository->save($webhostingDomainName2);
        $this->repository->save($webhostingDomainName3);
        $this->repository->save($webhostingDomainName4);
        $this->repository->save($webhostingDomainName5);
        $this->repository->save($webhostingDomainName6);

        // Must be done explicit, normally handled by a transaction script.
        $em->flush();
    }

    /** @test */
    public function it_gets_existing_domain_name(): void
    {
        $webhostingDomainName = $this->repository->get($this->id1);

        self::assertTrue($webhostingDomainName->getId()->equals($this->id1), 'ID should equal');
        self::assertEquals($this->space1, $webhostingDomainName->getSpace());
        self::assertEquals(new DomainNamePair('example', 'com'), $webhostingDomainName->getNamePair());
        self::assertTrue($webhostingDomainName->isPrimary());

        $webhostingDomainName = $this->repository->get($this->id2);

        self::assertTrue($webhostingDomainName->getId()->equals($this->id2), 'ID should equal');
        self::assertEquals($this->space2, $webhostingDomainName->getSpace());
        self::assertEquals(new DomainNamePair('example', 'net'), $webhostingDomainName->getNamePair());
        self::assertTrue($webhostingDomainName->isPrimary());

        $webhostingDomainName = $this->repository->get($this->id3);

        self::assertTrue($webhostingDomainName->getId()->equals($this->id3), 'ID should equal');
        self::assertEquals($this->space2, $webhostingDomainName->getSpace());
        self::assertEquals(new DomainNamePair('example', 'co.uk'), $webhostingDomainName->getNamePair());
        self::assertFalse($webhostingDomainName->isPrimary());
    }

    /** @test */
    public function it_gets_primary_of_space(): void
    {
        self::assertTrue($this->repository->getPrimaryOf($this->space1->getId())->getId()->equals($this->id1), 'ID should equal');
        self::assertTrue($this->repository->getPrimaryOf($this->space2->getId())->getId()->equals($this->id2), 'ID should equal');

        $this->expectException(WebhostingSpaceNotFound::class);
        $this->expectExceptionMessage(
            WebhostingSpaceNotFound::withId($id = SpaceId::fromString(self::SPACE_NOOP))->getMessage()
        );

        $this->repository->getPrimaryOf($id);
    }

    /** @test */
    public function it_gets_by_name(): void
    {
        $domainName1 = $this->repository->getByName(new DomainNamePair('example', 'com'));
        $domainName2 = $this->repository->getByName(new DomainNamePair('example', 'net'));
        $domainName3 = $this->repository->getByName(new DomainNamePair('example', 'co.uk'));

        self::assertNotNull($domainName1);
        self::assertNotNull($domainName2);

        self::assertTrue($domainName1->getId()->equals($this->id1), 'ID should equal');
        self::assertTrue($domainName2->getId()->equals($this->id2), 'ID should equal');
        self::assertTrue($domainName3->getId()->equals($this->id3), 'ID should equal');

        $this->expectExceptionObject(DomainNameNotFound::withName($name = new DomainNamePair('example', 'noop')));

        $this->repository->getByName($name);
    }

    /** @test */
    public function it_gets_all_accessible(): void
    {
        $this->assertEntitiesEquals([], $this->repository->allAccessibleBy(UserId::fromString(self::SPACE_NOOP)));
        $this->assertEntitiesEquals([$this->id1, $this->id5], $this->repository->allAccessibleBy(UserId::fromString(self::OWNER_ID1)));
        $this->assertEntitiesEquals([$this->id2, $this->id3], $this->repository->allAccessibleBy(UserId::fromString(self::OWNER_ID2)));
        $this->assertEntitiesEquals([$this->id4, $this->id6], $this->repository->allAccessibleBy(null));
    }

    /**
     * @param array<int,object> $expectedIds
     */
    private function assertEntitiesEquals(array $expectedIds, iterable $result): void
    {
        $found = [];
        $expected = [];

        foreach ($result as $entity) {
            $found[$entity->id->toString()] = $entity;
        }

        foreach ($expectedIds as $id) {
            $expected[$id->toString()] = $this->repository->get($id);
        }

        \ksort($expected, SORT_STRING);
        \ksort($found, SORT_STRING);

        self::assertEquals($expected, $found);
    }

    /** @test */
    public function it_removes_an_secondary_domain_name(): void
    {
        $webhostingDomainName = $this->repository->get($this->id3);

        $this->repository->remove($webhostingDomainName);
        $this->getEntityManager()->flush();

        $this->expectException(DomainNameNotFound::class);
        $this->expectExceptionMessage(DomainNameNotFound::withId($this->id3)->getMessage());

        $this->repository->get($this->id3);
    }

    /** @test */
    public function it_cannot_remove_a_primary_domain_name(): void
    {
        $webhostingDomainName = $this->repository->get($this->id1);

        $this->expectException(CannotRemovePrimaryDomainName::class);
        $this->expectExceptionMessage(
            (new CannotRemovePrimaryDomainName($this->id1, $webhostingDomainName->getSpace()->getId()))->getMessage()
        );

        $this->repository->remove($webhostingDomainName);
    }

    /** @test */
    public function it_marks_previous_primary_as_secondary(): void
    {
        $primaryDomainName = $this->repository->get($this->id2);
        $secondaryDomainName = $this->repository->get($this->id3);

        $secondaryDomainName->markPrimary();
        $this->repository->save($secondaryDomainName);

        self::assertTrue($secondaryDomainName->isPrimary());
        self::assertFalse($primaryDomainName->isPrimary());
    }
}
