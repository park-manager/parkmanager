<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain;

use Closure;
use Generator;
use PHPUnit\Framework\Assert;
use Throwable;

/**
 * Helps to quickly set-up an in-memory repository.
 *
 * @template T
 */
trait MockRepository
{
    /** @var array<string,T> */
    protected array $storedById = [];

    /** @var array<string,T> */
    protected array $savedById = [];

    /** @var array<string,T> */
    protected array $removedById = [];

    /**
     * Counter of saved entities (in total).
     */
    protected int $mockWasSaved = 0;

    /**
     * Count of removed entities (in total).
     */
    protected int $mockWasRemoved = 0;

    /** @var array<string,string<object>> [mapping-name][index-key] => {entity} */
    protected array $storedByField = [];

    /** @var array<string,string<object>> [mapping-name][index-key] => {entity} */
    protected array $storedMultiByField = [];

    /**
     * @psalm-param array<mixed,T> $initialEntities Array of initial entities (these are not counted as saved)
     */
    public function __construct(array $initialEntities = [])
    {
        foreach ($initialEntities as $entity) {
            $this->setInMockedStorage($entity);
        }
    }

    /**
     * @param-param T $entity
     */
    private function setInMockedStorage(object $entity): void
    {
        $this->storedById[$this->getValueWithGetter($entity, 'id')->toString()] = $entity;

        $indexMapping = $this->getFieldsIndexMapping();

        foreach ($indexMapping as $mapping => $getter) {
            $withGetter = $this->getValueWithGetter($entity, $getter);
            $this->storedByField[$mapping][$withGetter] = $entity;
        }

        foreach ($this->getFieldsIndexMultiMapping() as $mapping => $getter) {
            if (isset($indexMapping[$mapping])) {
                throw new \RuntimeException(\sprintf('Multi-mapping name "%s" already exists in single mapping.', $mapping));
            }

            $withGetter = $this->getValueWithGetter($entity, $getter);
            $this->storedMultiByField[$mapping][$withGetter][] = $entity;
        }
    }

    /**
     * @param Closure|string $getter
     */
    private function getValueWithGetter(object $object, $getter)
    {
        if ($getter instanceof Closure) {
            return $getter($object);
        }

        if (\mb_strpos($getter, '#') === 0) {
            return $object->{\mb_substr($getter, 1)};
        }

        switch (true) {
            case \method_exists($object, $getter):
                return $object->{$getter}();

            case \method_exists($object, 'get' . \ucfirst($getter)):
                return $object->{'get' . \ucfirst($getter)}();

            case \property_exists($object, $getter):
                return $object->{$getter};

            default:
                throw new \InvalidArgumentException(\sprintf('Unable to get field value for "%s" with getter "%s", neither "%2$s()", "get%3$s()" or property "%2$s" exists.', \get_class($object), $getter, \ucfirst($getter)));
        }
    }

    /**
     * Returns a list fields (#property, method-name or Closure for extracting)
     * to use for mapping the entity in storage.
     *
     * @return array<string,Closure|string> [mapping-name] => '#property or method'
     */
    protected function getFieldsIndexMapping(): array
    {
        return [];
    }

    /**
     * Returns a list fields (#property, method-name or Closure for extracting)
     * to use for mapping the entity in storage.
     *
     * @return array<string,Closure|string> [mapping-name] => '#property or method'
     */
    protected function getFieldsIndexMultiMapping(): array
    {
        return [];
    }

    /**
     * @psalm-param T $entity
     */
    protected function mockDoSave(object $entity): void
    {
        $this->setInMockedStorage($entity);
        $this->savedById[$this->getValueWithGetter($entity, 'id')->toString()] = $entity;
        ++$this->mockWasSaved;
    }

    /**
     * @psalm-param T $entity
     */
    protected function mockDoRemove(object $entity): void
    {
        $this->removedById[$this->getValueWithGetter($entity, 'id')->toString()] = $entity;
        ++$this->mockWasRemoved;
    }

    protected function mockDoGetById(object $id)
    {
        if (! isset($this->storedById[$id->toString()])) {
            $this->throwOnNotFound($id);
        }

        $this->guardNotRemoved($id);

        return $this->storedById[$id->toString()];
    }

    protected function guardNotRemoved(object $id): void
    {
        if (isset($this->removedById[$id->toString()])) {
            $this->throwOnNotFound($id);
        }
    }

    /**
     * @psalm-return T
     *
     * @param float|int|string|null $value
     */
    protected function mockDoGetByField(string $key, $value)
    {
        if (! isset($this->storedByField[$key][$value])) {
            $this->throwOnNotFound($value);
        }

        $entity = $this->storedByField[$key][$value];
        $this->guardNotRemoved($this->getValueWithGetter($entity, 'id'));

        return $entity;
    }

    /**
     * @psalm-return Generator<T>
     *
     * @param float|int|string|null $value
     */
    protected function mockDoGetMultiByField(string $key, $value): Generator
    {
        if (! isset($this->storedMultiByField[$key][$value])) {
            $this->throwOnNotFound($value);
        }

        foreach ($this->storedMultiByField[$key][$value] as $entity) {
            $id = $this->getValueWithGetter($entity, 'id');

            if (isset($this->removedById[$id->toString()])) {
                continue;
            }

            yield $entity;
        }
    }

    /**
     * @param float|int|string|null $value
     */
    protected function mockDoHasByField(string $key, $value): bool
    {
        if (! isset($this->storedByField[$key][$value])) {
            return false;
        }

        if (isset($this->removedById[$this->getValueWithGetter($this->storedByField[$key][$value], 'id')->toString()])) {
            return false;
        }

        return true;
    }

    /**
     * @throws Throwable
     */
    abstract protected function throwOnNotFound($key): void;

    public function assertNoEntitiesWereSaved(): void
    {
        Assert::assertEquals(0, $this->mockWasSaved, 'No entities were expected to be stored');
    }

    public function resetRecordingState(): void
    {
        $this->mockWasSaved = 0;
        $this->mockWasRemoved = 0;
    }

    /**
     * @param-param array<int,T> $entities
     */
    public function assertEntitiesWereSaved(array $entities = []): void
    {
        Assert::assertGreaterThan(0, $this->mockWasSaved, 'Entities were expected to be stored');

        if ($entities) {
            Assert::assertEquals($entities, \array_values($this->savedById));
        }
    }

    public function assertEntitiesCountWasSaved(int $count): void
    {
        Assert::assertEquals($count, $this->mockWasSaved, 'Entities were expected to be stored');
    }

    public function assertNoEntitiesWereRemoved(): void
    {
        if ($this->mockWasRemoved > 0) {
            Assert::fail(\sprintf('No entities were expected to be removed, but %d entities were removed.', $this->mockWasSaved));
        }
    }

    /**
     * @param-param array<int,T> $entities
     */
    public function assertEntitiesWereRemoved(array $entities): void
    {
        Assert::assertGreaterThan(0, $this->mockWasRemoved, 'No entities were removed');
        Assert::assertEquals($entities, \array_values($this->removedById));
    }

    public function assertHasEntity($id, Closure $excepted): void
    {
        $key = (string) $id;
        Assert::assertArrayHasKey($key, $this->storedById);
        $excepted($this->storedById[$key]);
    }

    public function assertHasEntityThat(Closure $excepted): void
    {
        foreach ($this->storedById as $entity) {
            if ($excepted($entity)) {
                return;
            }
        }

        Assert::fail('No entity was found that gave a Closure condition.');
    }
}
