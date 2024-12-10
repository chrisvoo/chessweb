<?php

namespace App\Domain\Tag;

use App\Domain\SimpleNamedEntity;
use ReflectionClass;

class Tag extends SimpleNamedEntity
{
    public const TABLE_NAME = 'tags';

    public static function getSortableFields(): array
    {
        $props = (new ReflectionClass(__CLASS__))->getProperties();
        return array_map(fn ($prop) => $prop->getName(), $props);
    }
}
