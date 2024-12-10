<?php

namespace App\Infrastructure\Persistence\Tag;

use App\Domain\Operations\DatabaseOperation;
use App\Domain\Pagination\SimpleNamedFilters;
use App\Domain\Tag\Tag;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use DateTime;
use PDO;
use Psr\Log\LoggerInterface;

class TagRepository implements TagRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManagerInterface $databaseManager,
        protected LoggerInterface $logger
    ) {
        $this->databaseManager->connect();
    }

    /**
     * @return Tag[]
     */
    public function list(SimpleNamedFilters $filters): array
    {
        $table = Tag::TABLE_NAME;
        $whereCondition = isset($filters->name) ? "name LIKE :name" : '';
        $params = isset($filters->name) ? ['name' => "%{$filters->name}%"] : [];
        $orderBy = isset($filters->sortBy) ? "{$filters->sortBy}" : 'name';
        $orderDirection = isset($filters->sortOrder) ? "{$filters->sortOrder->value}" : 'ASC';
        $offset = $filters->offset ?? 0;
        $limit = $filters->limit ?? 10;

        return $this->databaseManager->rows(<<<SQL
            SELECT id, name, created_at, updated_at
            FROM $table
            WHERE $whereCondition
            ORDER BY $orderBy $orderDirection
            OFFSET $offset LIMIT $limit
SQL,
            $params,
            PDO::FETCH_CLASS,
            Tag::class
        );
    }

    /**
     * Upsert of a user
     * @param Tag $tag
     * @return DatabaseOperation
     */
    public function save(Tag $tag): DatabaseOperation
    {
        if (isset($tag->id)) {
            $tagExist = $this->findById($tag->id);
            if (!$tagExist) {
                $dbOp = new DatabaseOperation();
                $dbOp->success = false;
                $dbOp->message = 'Tag not found';
                $dbOp->entityId = $tag->id;
                return $dbOp;
            }

            $affectedRows = $this->databaseManager->update(
                Tag::TABLE_NAME,
                [
                    'name' => $tag->name,
                    'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
                ],
                ['id' => $tag->id]
            );

            $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated($tag->id);
            $dbOp->affectedRows = $affectedRows;
            return $dbOp;
        } else {
            $lastInsertedId = $this->databaseManager->insert(
                Tag::TABLE_NAME,
                [
                    'name' => $tag->name,
                    'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
                ]
            );

            return DatabaseOperation::newSingleEntitySuccessfullyCreated((int)$lastInsertedId);
        }
    }

    /**
     * Delete a user
     * @param int $tagId
     * @return DatabaseOperation
     */
    public function delete(int $tagId): DatabaseOperation
    {
        $affectedRows = $this->databaseManager->deleteById(Tag::TABLE_NAME, $tagId);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated($tagId);
        $dbOp->affectedRows = $affectedRows;
        return $dbOp;
    }

    public function findById(int $id): Tag|false
    {
        $table = Tag::TABLE_NAME;
        /**
         * @var Tag|false $result
         */
        $result = $this->databaseManager->row(<<<SQL
            SELECT id, name, created_at, updated_at
            FROM $table
            WHERE id = :id
SQL,
            Tag::class,
            ['id' => $id]
        );

        return $result;
    }
}
