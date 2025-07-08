<?php

namespace App\Infrastructure\Persistence\ArticlesCategories;

use App\Domain\Operations\DatabaseOperation;

interface ArticlesCategoriesRepositoryInterface
{
    public function deleteCategoriesForArticle(int $articleId): DatabaseOperation;

    /**
     * Save the tags for the specified articleId
     * @param int $articleId
     * @param array $fields The names of the columns, important for the order of rows to be inserted
     * @param int[] $rows A list of records to insert
     */
    public function saveCategoriesForArticle(int $articleId, array $fields, array $rows): DatabaseOperation;
}
