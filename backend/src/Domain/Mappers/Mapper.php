<?php

namespace App\Domain\Mappers;

use InvalidArgumentException;

class Mapper implements MapperInterface
{
    /**
     * Maps an array of key-value pairs to an object
     * @template T
     * @param array $data An array of key-value pairs whose keys are the names of the object's properties
     * @param class-string<T> $class
     * @return T
     */
    public function map(array $data, string $class)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class $class does not exist.");
        }

        $instance = new $class();
        foreach ($data as $property => $value) {
            // Check if the property exists in the class
            if (property_exists($class, $property)) {
                $instance->$property = $value;
            }
        }

        return $instance;
    }
}
