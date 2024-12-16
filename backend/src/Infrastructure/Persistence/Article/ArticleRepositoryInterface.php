<?php

namespace App\Infrastructure\Persistence\Article;

use App\Application\Actions\Article\Filters\ArticleFilters;
use App\Domain\Article\Article;
use App\Domain\Operations\DatabaseOperation;

interface ArticleRepositoryInterface
{
    public function findById(int $id): Article|false;

    /**
     * List articles and eventually filter them.
     * @return Article[]
     */
    public function list(ArticleFilters $filters): array;

    public function count(ArticleFilters $filters): int;

    /**
     * Upsert of a article
     * @param Article $article
     * @return DatabaseOperation
     */
    public function save(Article $article): DatabaseOperation;

    /**
     * Delete an article
     * @param int $articleId
     * @return DatabaseOperation
     */
    public function delete(int $articleId): DatabaseOperation;
}
