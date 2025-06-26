<?php

namespace App\Infrastructure\Persistence\Category;

use App\Domain\Category\Category;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\Pagination\SimpleNamedFilters;
use App\Infrastructure\Persistence\UniquenessCheckInterface;

interface CategoryRepositoryInterface extends UniquenessCheckInterface
{
    /**
     * It returns the number of categories matching the filters
     * @param SimpleNamedFilters $filters
     * @return int
     */
    public function count(SimpleNamedFilters $filters): int;

    /**
     * List categories and eventually filter them.
     * @return Category[]
     */
    public function list(SimpleNamedFilters $filters): array;

    public function findById(int $id): Category|false;

    /**
     * Upsert of a category
     * @param Category $category
     * @return DatabaseOperation
     */
    public function save(Category $category): DatabaseOperation;

    /**
     * Delete a category
     * @param int $categoryId
     * @return DatabaseOperation
     */
    public function delete(int $categoryId): DatabaseOperation;

    public function getCategoryCloud(int $limit = 10): array;
}
