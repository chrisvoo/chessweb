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
     * @param array<string, array> $mapNestedProperties
     * @return T
     */
    public function map(array $data, string $class, array $mapNestedProperties = [])
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class $class does not exist.");
        }

        $instance = new $class();
        foreach ($data as $property => $value) {
            // Check if the property exists in the class
            if (property_exists($class, $property)) {
                if (isset($mapNestedProperties[$property])) {
                    $nestedObjectProps = $mapNestedProperties[$property];
                    if (!class_exists($nestedObjectProps['class'])) {
                        throw new InvalidArgumentException(
                            "Class " . $nestedObjectProps['class'] . " does not exist."
                        );
                    }
                    if ($nestedObjectProps['is_list'] ?? false) {
                        $nestedInstances = [];
                        foreach ($value as $classProps) {
                            $nestedInstances[] = $this->map($classProps, $nestedObjectProps['class']);
                        }
                        $instance->$property = $nestedInstances;
                    } else {
                        $instance->$property = $this->map($value, $nestedObjectProps['class']);
                    }
                } else {
                    $instance->$property = $value;
                }
            }
        }

        return $instance;
    }
}
