<?php

namespace App\Infrastructure\Persistence\ArticlesTags;

use App\Domain\Operations\DatabaseOperation;

interface ArticlesTagsRepositoryInterface
{
    public function deleteTagsForArticle(int $articleId): DatabaseOperation;

    /**
     * Save the tags for the specified articleId
     * @param int $articleId
     * @param array $fields The names of the columns, important for the order of rows to be inserted
     * @param int[] $rows A list of tag IDs
     * @return DatabaseOperation
     */
    public function saveTagsForArticle(int $articleId, array $fields, array $rows): DatabaseOperation;
}
