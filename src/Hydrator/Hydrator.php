<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2024 Amanda Sharief
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\Hydrator;

use ReflectionClass;

class Hydrator implements HydratorInterface
{
    private static array $cache = [];

    /**
     * Hydrates an object with data
     */
    public function hydrate(object $object, array $data): void
    {
        $props = $this->getProperties($object);

        foreach ($data as $key => $value) {
            if (isset($props[$key])) {
                $props[$key]->setValue($object, $value);
            }
        }
    }

    /**
     * Extracts data from an object
     * 
     * @internal uninitalized properties are ignored as these are values which are not set.
     */
    public function extract(object $object): array
    {
        $props = $this->getProperties($object);

        $result = [];
        foreach ($props as $name => $prop) {
            if($prop->isInitialized($object)){
                $result[$name] = $prop->getValue($object);
            }
        }
        return $result;
    }

    /**
     * Gets the propertiers and caches results so reflection class is only called once
     * per class type
     */
    protected function getProperties(object $object): array
    {
        $class = $object::class;

        if (isset(static::$cache[$class])) {
            return static::$cache[$class];
        }

        $props = (new ReflectionClass($class))->getProperties();

        static::$cache[$class] = [];
        foreach ($props as $prop) {
            static::$cache[$class][$prop->getName()] = $prop;
        }
       
        return static::$cache[$class];
    }
}
