<?php

namespace App\Domain\Pagination;

trait SortingTrait
{
    public string $sortBy;
    public SortDirection $sortOrder;
}
