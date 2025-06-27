<?php

namespace App\Application\Actions\Article\Filters;

use App\Domain\Pagination\PaginationTrait;
use App\Domain\Pagination\SortingTrait;

class ArticleFilters
{
    use SortingTrait;
    use PaginationTrait;

    public ?string $searchText;
    public ?int $categoryId;
    public ?int $tagId;
    public ?string $createdFrom;
    public ?string $createdTo;
    public ?bool $skipContent;
}
