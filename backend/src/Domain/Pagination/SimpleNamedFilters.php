<?php

namespace App\Domain\Pagination;

class SimpleNamedFilters
{
    use SortingTrait;
    use PaginationTrait;

    public bool $all_items;
    public string $name;
}
