<?php declare(strict_types=1);

namespace Lightning\Test\Entity;

use ReflectionClass;
use ReflectionProperty;

/**
 * Brought this back to aid with existing tests after refactor
 */
abstract class AbstractEntity 
{
    public function toState(): array
    {
        $reflection = new ReflectionClass($this);
        $data = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE) as $reflectionProperty) {
            $reflectionProperty->setAccessible(true); // From 8.1 this has no effect and is not required

            if (! $reflectionProperty->isInitialized($this)) {
                continue;
            }

            $value = $reflectionProperty->getValue($this);

            if ($value instanceof AbstractEntity) {
                $value = $value->toState();
            } elseif (is_iterable($value)) {
                $value = $this->fromIterable($value);
            }

            $data[$reflectionProperty->getName()] = $value;
        }

        return $data;
    }

    private function fromIterable(iterable $items): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            if (is_iterable($value)) {
                $value = $this->fromIterable($items);
            }
            $result[$key] = $value instanceof  AbstractEntity ? $value->toState() : $value;
        }

        return $result;
    }
}