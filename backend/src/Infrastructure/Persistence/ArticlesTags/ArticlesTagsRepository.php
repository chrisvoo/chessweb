<?php

namespace App\Infrastructure\Persistence\ArticlesTags;

use App\Domain\ArticlesTags\ArticlesTags;
use App\Domain\Operations\DatabaseOperation;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use Psr\Log\LoggerInterface;

class ArticlesTagsRepository implements ArticlesTagsRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManagerInterface $databaseManager,
        protected LoggerInterface $logger,
    ) {
    }

    public function deleteTagsForArticle(int $articleId): DatabaseOperation
    {
        $affectedRows = $this->databaseManager->delete(
            ArticlesTags::TABLE_NAME,
            ['article_id' => $articleId]
        );

        return DatabaseOperation::newEntityOperation(
            "Tags for article $articleId have been deleted.",
            DatabaseOperation::ENTITY_DELETED,
            null,
            $affectedRows
        );
    }

    /**
     * Save the tags for the specified articleId
     * @param int $articleId
     * @param array $fields The names of the columns, important for the order of rows to be inserted
     * @param array $rows A list of records to insert
     * @return DatabaseOperation
     */
    public function saveTagsForArticle(int $articleId, array $fields, array $rows): DatabaseOperation
    {
        $stmt = $this->databaseManager->batchInsert(
            ArticlesTags::TABLE_NAME,
            $fields,
            $rows
        );
        $affectedRows = $stmt->rowCount();

        return DatabaseOperation::newEntityOperation(
            "Tags for article $articleId have been saved.",
            DatabaseOperation::ENTITY_CREATED,
            null,
            $affectedRows
        );
    }
}
