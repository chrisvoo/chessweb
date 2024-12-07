<?php

namespace App\Domain\Mappers;

interface MapperInterface
{
    /**
     * Maps an array of key-value pairs to an object
     * @template T
     * @param array $data An array of key-value pairs whose keys are the names of the object's properties
     * @param class-string<T> $class
     * @return T
     */
    public function map(array $data, string $class);
}
