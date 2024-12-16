<?php

namespace App\Application\Actions\Article\Filters;

use App\Domain\Pagination\PaginationTrait;
use App\Domain\Pagination\SortingTrait;

class ArticleFilters
{
    use SortingTrait;
    use PaginationTrait;

    public ?string $search_text;
    public ?int $category_id;
    public ?int $tag_id;
    public ?string $created_from;
    public ?string $created_to;
}
