<?php

namespace App\Infrastructure\Persistence\Article;

use App\Application\Actions\Article\Filters\ArticleFilters;
use App\Domain\Article\Article;
use App\Domain\Article\ArticleNotFoundException;
use App\Domain\Operations\DatabaseOperation;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use DateTime;
use PDO;
use Psr\Log\LoggerInterface;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManagerInterface $databaseManager,
        protected LoggerInterface $logger
    ) {
        $this->databaseManager->connect();
    }

    public function findById(int $id): Article|false
    {
        $table = Article::TABLE_NAME;
        /**
         * @var Article|false $result
         */
        $result = $this->databaseManager->row(<<<SQL
            SELECT id, title, author_id, content, created_at, updated_at
            FROM $table
            WHERE id = :id
SQL,
            Article::class,
            ['id' => $id]
        );

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function list(ArticleFilters $filters): array
    {
        $this->logger->debug('repo articles list filters', [var_export($filters, true)]);

        $orderBy = isset($filters->sortBy) ? "{$filters->sortBy}" : 'created_at';
        $orderDirection = isset($filters->sortOrder) ? "{$filters->sortOrder->value}" : 'DESC';
        $offset = $filters->offset ?? 0;
        $limit = $filters->limit ?? 10;
        $params = [];

        $able = Article::TABLE_NAME;
        $sql = "SELECT id, title, author_id, content, created_at, updated_at 
                FROM $able a ";

        if (!empty($filters->search_text)) {
            $sql .= "WHERE (a.title LIKE :search_text OR a.content LIKE :search_text) ";
            $params['search_text'] = "%{$filters->search_text}%";
        } elseif(!empty($filters->tagId)) {
            $sql .= "INNER JOIN article_tag at ON a.id = at.article_id WHERE at.tag_id = :tag_id ";
            $params['tag_id'] = $filters->tagId;
        } elseif(!empty($filters->categoryId)) {
            $sql .= "INNER JOIN article_categories ac ON a.id = ac.article_id WHERE ac.category_id = :category_id ";
            $params['category_id'] = $filters->categoryId;
        } elseif(!empty($filters->createdFrom) && !empty($filters->createdTo)) {
            $sql .= "WHERE a.created_at BETWEEN :created_from AND :created_to ";
            $params['created_from'] = $filters->createdFrom;
            $params['created_to'] = $filters->createdTo;
        }

        $sql .= "ORDER BY $orderBy $orderDirection LIMIT $limit OFFSET $offset";
        $this->logger->debug('list SQL', ['sql' => $sql, 'params' => $params]);

        return $this->databaseManager->rows(
            $sql,
            $params,
            PDO::FETCH_CLASS,
            Article::class
        );
    }

    public function count(ArticleFilters $filters): int
    {
        $able = Article::TABLE_NAME;
        $sql = "SELECT id FROM $able a ";
        $params = [];

        if (!empty($filters->search_text)) {
            $sql .= "WHERE (a.title LIKE :search_text OR a.content LIKE :search_text) ";
            $params['search_text'] = "%{$filters->search_text}%";
        } elseif(!empty($filters->tagId)) {
            $sql .= "INNER JOIN article_tag at ON a.id = at.article_id WHERE at.tag_id = :tag_id ";
            $params['tag_id'] = $filters->tagId;
        } elseif(!empty($filters->categoryId)) {
            $sql .= "INNER JOIN article_categories ac ON a.id = ac.article_id WHERE ac.category_id = :category_id ";
            $params['category_id'] = $filters->categoryId;
        } elseif(!empty($filters->createdFrom) && !empty($filters->createdTo)) {
            $sql .= "WHERE a.created_at BETWEEN :created_from AND :created_to ";
            $params['created_from'] = $filters->createdFrom;
            $params['created_to'] = $filters->createdTo;
        }

        return $this->databaseManager->count(
            $sql,
            $params
        );
    }

    /**
     * @inheritDoc
     * @throws ArticleNotFoundException
     */
    public function save(Article $article): DatabaseOperation
    {
        if (isset($article->id)) {
            $userExist = $this->findById($article->id);

            if (!$userExist) {
                throw new ArticleNotFoundException();
            }

            $affectedRows = $this->databaseManager->update(
                $article::TABLE_NAME,
                [
                    'title' => $article->title,
                    'content' => $article->content,
                    'author_id' => $article->author_id,
                    'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
                ],
                ['id' => $article->id]
            );

            $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated($article->id);
            $dbOp->affectedRows = $affectedRows;
            return $dbOp;
        } else {
            $lastInsertedId = $this->databaseManager->insert(
                Article::TABLE_NAME,
                [
                    'title' => $article->title,
                    'content' => $article->content,
                    'author_id' => $article->author_id,
                    'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
                ]
            );

            return DatabaseOperation::newSingleEntitySuccessfullyCreated((int)$lastInsertedId);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(int $articleId): DatabaseOperation
    {
        $affectedRows = $this->databaseManager->deleteById(Article::TABLE_NAME, $articleId);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted($articleId);
        $dbOp->affectedRows = $affectedRows;
        return $dbOp;
    }
}
