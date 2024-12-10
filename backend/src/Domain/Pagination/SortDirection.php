<?php

namespace App\Domain\Pagination;

enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';
}
