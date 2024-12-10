<?php

namespace App\Domain\Pagination;

class SimpleNamedFilters
{
    use SortingTrait;
    use PaginationTrait;

    public string $name;
}
