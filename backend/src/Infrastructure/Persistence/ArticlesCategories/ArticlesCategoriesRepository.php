<?php

namespace App\Infrastructure\Persistence\ArticlesCategories;

use App\Domain\ArticlesCategories\ArticlesCategories;
use App\Domain\Operations\DatabaseOperation;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use Psr\Log\LoggerInterface;

class ArticlesCategoriesRepository implements ArticlesCategoriesRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManagerInterface $databaseManager,
        protected LoggerInterface $logger,
    ) {
    }

    public function deleteCategoriesForArticle(int $articleId): DatabaseOperation
    {
        $affectedRows = $this->databaseManager->delete(
            ArticlesCategories::TABLE_NAME,
            ['article_id' => $articleId]
        );

        return DatabaseOperation::newEntityOperation(
            "Categories for article $articleId have been deleted.",
            DatabaseOperation::ENTITY_DELETED,
            null,
            $affectedRows
        );
    }

    /**
     * Save the tags for the specified articleId
     * @param int $articleId
     * @param array $fields The names of the columns, important for the order of rows to be inserted
     * @param int[] $rows A list of category IDs
     * @return DatabaseOperation
     */
    public function saveCategoriesForArticle(int $articleId, array $fields, array $rows): DatabaseOperation
    {
        $stmt = $this->databaseManager->batchInsert(
            ArticlesCategories::TABLE_NAME,
            $fields,
            $rows
        );
        $affectedRows = $stmt->rowCount();

        return DatabaseOperation::newEntityOperation(
            "Categories for article $articleId have been saved.",
            DatabaseOperation::ENTITY_CREATED,
            null,
            $affectedRows
        );
    }
}
