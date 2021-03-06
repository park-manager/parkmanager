<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use Generator;
use InvalidArgumentException;
use JsonSerializable;
use ParkManager\Domain\UuidTrait;
use ParkManager\Tests\Mock\Domain\MockRepository;
use PHPUnit\Framework\TestCase;
use Serializable;

/**
 * @internal
 */
final class MockRepositoryTest extends TestCase
{
    /** @test */
    public function it_has_no_entities_saved_or_removed(): void
    {
        $repository = new class() {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }
        };

        $repository->assertNoEntitiesWereSaved();
        $repository->assertNoEntitiesWereRemoved();
    }

    /** @test */
    public function it_gets_entity(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            public function get(MockIdentity $id): MockEntity
            {
                return $this->mockDoGetById($id);
            }
        };

        $repository->assertNoEntitiesWereSaved();
        $repository->assertNoEntitiesWereRemoved();
        $repository->assertHasEntity($entity1->id(), static function (): void { });
        $repository->assertHasEntity($entity2->id(), static function (): void { });
        self::assertSame($entity1, $repository->get($entity1->id()));
        self::assertSame($entity2, $repository->get($entity2->id()));
    }

    /** @test */
    public function it_gets_multiple_entities(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'bla', 'example.com');
        $entity2 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'bla', 'example.com');
        $entity3 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'barfoo', 'example2.com');
        $entity4 = new MockEntity('566eb8e3-d9ba-4d6d-8d3c-c4a744df85ae', 'foobar', 'example2.com');
        $entity5 = new MockEntity('f1acc3fb-de6a-4fc4-af6e-dde2327b4425', 'foobar', 'example2.com');

        $repository = new class([$entity1, $entity2, $entity3, $entity4, $entity5]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            public function get(MockIdentity $id): MockEntity
            {
                return $this->mockDoGetById($id);
            }

            public function all(string $key): Generator
            {
                return $this->mockDoGetMultiByField('domain', $key);
            }

            public function remove(MockEntity $entity): void
            {
                $this->mockDoRemove($entity);
            }

            protected function getFieldsIndexMultiMapping(): array
            {
                return [
                    'domain' => 'getDomain',
                ];
            }
        };

        $repository->remove($entity5);

        $repository->assertHasEntity($entity1->id(), static function (): void { });
        $repository->assertHasEntity($entity3->id(), static function (): void { });

        self::assertEquals([$entity1, $entity2], [...$repository->all('example.com')]);
        self::assertEquals([$entity3, $entity4], [...$repository->all('example2.com')]);
    }

    /** @test */
    public function it_gets_entity_by_field_method(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'John');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'Jane');

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            protected function getFieldsIndexMapping(): array
            {
                return ['last_name' => 'lastName'];
            }

            public function getByLastName(string $name): MockEntity
            {
                return $this->mockDoGetByField('last_name', $name);
            }
        };

        self::assertSame($entity1, $repository->getByLastName('John'));
        self::assertSame($entity2, $repository->getByLastName('Jane'));
    }

    /** @test */
    public function it_gets_entity_by_field_property(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity1->name = 'John';

        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');
        $entity2->name = 'Jane';

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            protected function getFieldsIndexMapping(): array
            {
                return ['Name' => '#name'];
            }

            public function getByName(string $name): MockEntity
            {
                return $this->mockDoGetByField('Name', $name);
            }
        };

        self::assertSame($entity1, $repository->getByName('John'));
        self::assertSame($entity2, $repository->getByName('Jane'));
    }

    /** @test */
    public function it_gets_entity_by_field_closure(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'John');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'Jane');

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            protected function getFieldsIndexMapping(): array
            {
                return ['last_name' => static fn (MockEntity $entity) => \mb_strtolower($entity->lastName())];
            }

            public function getByLastName(string $name): MockEntity
            {
                return $this->mockDoGetByField('last_name', $name);
            }
        };

        self::assertSame($entity1, $repository->getByLastName('john'));
        self::assertSame($entity2, $repository->getByLastName('jane'));
    }

    /** @test */
    public function it_saves_entity(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity1->name = 'John';

        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');
        $entity2->name = 'Jane';

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            protected function getFieldsIndexMapping(): array
            {
                return ['Name' => '#name'];
            }

            public function getByName(string $name): MockEntity
            {
                return $this->mockDoGetByField('Name', $name);
            }

            public function save(MockEntity $entity): void
            {
                $this->mockDoSave($entity);
            }
        };

        $entity1->name = 'Jones';

        $repository->save($entity1);

        $repository->assertEntitiesWereSaved();
        $repository->assertNoEntitiesWereRemoved();
        self::assertSame($entity1, $repository->getByName('Jones'));
        self::assertSame($entity2, $repository->getByName('Jane'));
    }

    /** @test */
    public function it_removes_entity(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            public function get(MockIdentity $id): MockEntity
            {
                return $this->mockDoGetById($id);
            }

            public function remove(MockEntity $entity): void
            {
                $this->mockDoRemove($entity);
            }
        };

        $repository->remove($entity1);
        $repository->assertNoEntitiesWereSaved();

        $repository->assertEntitiesWereRemoved([$entity1]);
        $repository->assertHasEntity($entity2->id(), static function (): void { });
        self::assertSame($entity2, $repository->get($entity2->id()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No, I has not have that key: ' . $entity1->id());

        $repository->get($entity1->id());
    }
}

/** @internal */
final class MockIdentity implements Serializable, JsonSerializable
{
    use UuidTrait;
}

/** @internal */
final class MockEntity
{
    private MockIdentity $id;

    public ?string $name = null;

    private string $lastName;

    private ?string $domain = null;

    public function __construct(string $id = 'fc86687e-0875-11e9-9701-acbc32b58315', string $name = 'Foobar', string $domain = null)
    {
        $this->id = MockIdentity::fromString($id);
        $this->lastName = $name;
        $this->domain = $domain;
    }

    public function id(): MockIdentity
    {
        return $this->id;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }
}
