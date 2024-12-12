<?php

namespace App\Infrastructure\Persistence\Category;

use App\Domain\Category\Category;
use App\Domain\Category\CategoryNotFoundException;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\Pagination\SimpleNamedFilters;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use DateTime;
use PDO;
use Psr\Log\LoggerInterface;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManagerInterface $databaseManager,
        protected LoggerInterface $logger
    ) {
        $this->databaseManager->connect();
    }

    /**
     * It returns the number of categories matching the filters
     * @param SimpleNamedFilters $filters
     * @return int
     */
    public function count(SimpleNamedFilters $filters): int
    {
        $table = Category::TABLE_NAME;
        $whereCondition = isset($filters->name) ? "name LIKE :name" : '';
        $params = isset($filters->name) ? ['name' => "%{$filters->name}%"] : [];

        return $this->databaseManager->count(<<<SQL
            SELECT id
            FROM $table
            WHERE $whereCondition
SQL,
            $params
        );
    }

    /**
     * List categories and eventually filter them.
     * @return Category[]
     */
    public function list(SimpleNamedFilters $filters): array
    {
        $this->logger->debug('repo list filters', [var_export($filters, true)]);

        $table = Category::TABLE_NAME;
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
            Category::class
        );
    }

    public function findById(int $id): Category|false
    {
        $table = Category::TABLE_NAME;
        /**
         * @var Category|false $result
         */
        $result = $this->databaseManager->row(<<<SQL
            SELECT id, name, created_at, updated_at
            FROM $table
            WHERE id = :id
SQL,
            Category::class,
            ['id' => $id]
        );

        return $result;
    }

    /**
     * Upsert of a category
     * @param Category $category
     * @return DatabaseOperation
     * @throws CategoryNotFoundException
     */
    public function save(Category $category): DatabaseOperation
    {
        if (isset($category->id)) {
            $categoryExist = $this->findById($category->id);
            if (!$categoryExist) {
                throw new CategoryNotFoundException();
            }

            $affectedRows = $this->databaseManager->update(
                Category::TABLE_NAME,
                [
                    'name' => $category->name,
                    'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
                ],
                ['id' => $category->id]
            );

            $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated($category->id);
            $dbOp->affectedRows = $affectedRows;
            return $dbOp;
        } else {
            $lastInsertedId = $this->databaseManager->insert(
                Category::TABLE_NAME,
                [
                    'name' => $category->name,
                    'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
                ]
            );

            return DatabaseOperation::newSingleEntitySuccessfullyCreated((int)$lastInsertedId);
        }
    }

    /**
     * Delete a category
     * @param int $categoryId
     * @return DatabaseOperation
     */
    public function delete(int $categoryId): DatabaseOperation
    {
        $affectedRows = $this->databaseManager->deleteById(Category::TABLE_NAME, $categoryId);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted($categoryId);
        $dbOp->affectedRows = $affectedRows;
        return $dbOp;
    }
}
