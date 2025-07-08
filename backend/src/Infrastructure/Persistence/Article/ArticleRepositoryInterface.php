<?php

namespace App\Infrastructure\Persistence\Article;

use App\Application\Actions\Article\Filters\ArticleFilters;
use App\Domain\Article\Article;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\Tag\Tag;

interface ArticleRepositoryInterface
{
    public function findById(int $id): Article|false;

    public function findByIdWithExtraDetails(int $id, bool $withExtraDetails = true): Article|false;

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

    /**
     * @param Article $article
     * @param Tag[] $tags
     * @return DatabaseOperation
     */
    public function assignTagsToArticle(Article $article, array $tags): DatabaseOperation;
}
