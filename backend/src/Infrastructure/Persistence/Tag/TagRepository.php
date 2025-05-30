<?php

namespace App\Infrastructure\Persistence\Tag;

use App\Domain\Operations\DatabaseOperation;
use App\Domain\Pagination\SimpleNamedFilters;
use App\Domain\Tag\Tag;
use App\Domain\Tag\TagNotFoundException;
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
     * It returns the number of tags matching the filters
     * @param SimpleNamedFilters $filters
     * @return int
     */
    public function count(SimpleNamedFilters $filters): int
    {
        $table = Tag::TABLE_NAME;
        $whereCondition = isset($filters->name) ? "name LIKE :name" : '';
        $params = isset($filters->name) ? ['name' => "%{$filters->name}%"] : [];

        return $this->databaseManager->count(
            <<<SQL
            SELECT id
            FROM $table
            WHERE $whereCondition
SQL,
            $params
        );
    }

    /**
     * @return Tag[]
     */
    public function list(SimpleNamedFilters $filters): array
    {
        $this->logger->debug('repo list filters', [var_export($filters, true)]);

        $table = Tag::TABLE_NAME;
        $whereCondition = !empty($filters->name) ? "WHERE name LIKE :name" : '';
        $params = !empty($filters->name) ? ['name' => "%{$filters->name}%"] : [];
        $orderBy = isset($filters->sortBy) ? "{$filters->sortBy}" : 'name';
        $orderDirection = isset($filters->sortOrder) ? "{$filters->sortOrder->value}" : 'ASC';
        $offset = $filters->offset ?? 0;
        $limit = $filters->limit ?? 10;

        $sql = <<<SQL
            SELECT id, name, created_at, updated_at
            FROM $table
            $whereCondition
            ORDER BY $orderBy $orderDirection
            LIMIT $limit OFFSET $offset 
SQL;
        $this->logger->debug('list SQL', ['sql' => $sql, 'params' => $params]);

        return $this->databaseManager->rows(
            $sql,
            $params,
            PDO::FETCH_CLASS,
            Tag::class
        );
    }

    /**
     * Upsert of a user
     * @param Tag $tag
     * @return DatabaseOperation
     * @throws TagNotFoundException
     */
    public function save(Tag $tag): DatabaseOperation
    {
        if (isset($tag->id)) {
            $tagExist = $this->findById($tag->id);
            if (!$tagExist) {
                throw new TagNotFoundException();
            }

            if ($this->isDuplicatedEntity($tag->name, $tag->id)) {
                return DatabaseOperation::failed(
                    'Tag ' . $tag->name . ' already exists',
                    DatabaseOperation::ENTITY_DUPLICATED
                );
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
            if ($this->isDuplicatedEntity($tag->name)) {
                return DatabaseOperation::failed(
                    'Tag ' . $tag->name . ' already exists',
                    DatabaseOperation::ENTITY_DUPLICATED
                );
            }

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
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted($tagId);
        $dbOp->affectedRows = $affectedRows;
        return $dbOp;
    }

    public function findById(int $id): Tag|false
    {
        $table = Tag::TABLE_NAME;
        /**
         * @var Tag|false $result
         */
        $result = $this->databaseManager->row(
            <<<SQL
            SELECT id, name, created_at, updated_at
            FROM $table
            WHERE id = :id
SQL,
            Tag::class,
            ['id' => $id]
        );

        return $result;
    }

    public function isDuplicatedEntity(string $name, ?int $id = null): bool
    {
        $table = Tag::TABLE_NAME;
        $sql =
            <<<SQL
            SELECT id
            FROM $table
            WHERE LOWER(name) = :name
SQL;
        $params = [
            'name' => strtolower($name)
        ];

        if ($id !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $id;
        }
        /**
         * @var Tag|false $result
         */
        $result = $this->databaseManager->row($sql, Tag::class, $params);

        return !empty($result);
    }
}
