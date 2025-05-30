<?php

namespace App\Infrastructure\Persistence;

interface UniquenessCheckInterface
{
    /**
     * It checks if an entity with the specified name exists. In case of UPDATE,
     * you must specify also the ID.
     */
    public function isDuplicatedEntity(string $name, ?int $id = null): bool;
}
