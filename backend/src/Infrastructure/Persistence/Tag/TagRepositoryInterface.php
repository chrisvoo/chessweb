<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Tag;

use App\Domain\Operations\DatabaseOperation;
use App\Domain\Pagination\SimpleNamedFilters;
use App\Domain\Tag\Tag;

interface TagRepositoryInterface
{
    /**
     * It returns the number of tags matching the filters
     * @param SimpleNamedFilters $filters
     * @return int
     */
    public function count(SimpleNamedFilters $filters): int;

    /**
     * List tags and eventually filter them.
     * @return Tag[]
     */
    public function list(SimpleNamedFilters $filters): array;

    public function findById(int $id): Tag|false;

    /**
     * Upsert of a tag
     * @param Tag $tag
     * @return DatabaseOperation
     */
    public function save(Tag $tag): DatabaseOperation;

    /**
     * Delete a tag
     * @param int $tagId
     * @return DatabaseOperation
     */
    public function delete(int $tagId): DatabaseOperation;
}
