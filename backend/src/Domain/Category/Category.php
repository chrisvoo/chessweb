<?php

namespace App\Domain\Category;

use App\Domain\SimpleNamedEntity;
use ReflectionClass;

class Category extends SimpleNamedEntity
{
    public const TABLE_NAME = 'categories';

    public static function getSortableFields(): array
    {
        $props = (new ReflectionClass(__CLASS__))->getProperties();
        return array_map(fn ($prop) => $prop->getName(), $props);
    }
}
