<?php

namespace App\Infrastructure\Persistence\Category;

use App\Domain\Category\Category;
use App\Domain\Category\CategoryNotFoundException;
use App\Domain\Common\Slugger;
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
            SELECT id, name, slug, created_at, updated_at
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

    public function findBySlug(string $slug): Category|false
    {
        $table = Category::TABLE_NAME;
        /**
         * @var Category|false $result
         */
        $result = $this->databaseManager->row(
            <<<SQL
            SELECT id, name, slug, created_at, updated_at
            FROM $table
            WHERE slug = :slug
SQL,
            Category::class,
            ['slug' => $slug]
        );

        return $result;
    }

    public function findById(int $id): Category|false
    {
        $table = Category::TABLE_NAME;
        /**
         * @var Category|false $result
         */
        $result = $this->databaseManager->row(
            <<<SQL
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

            if ($this->isDuplicatedEntity($category->name, $categoryExist->id)) {
                return DatabaseOperation::failed(
                    'Category ' . $category->name . ' already exists',
                    DatabaseOperation::ENTITY_DUPLICATED
                );
            }

            $affectedRows = $this->databaseManager->update(
                Category::TABLE_NAME,
                [
                    'name' => $category->name,
                    'slug' => Slugger::generate($category->name),
                    'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
                ],
                ['id' => $category->id]
            );

            $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated($category->id);
            $dbOp->affectedRows = $affectedRows;
            return $dbOp;
        } else {
            if ($this->isDuplicatedEntity($category->name)) {
                return DatabaseOperation::failed(
                    'Category ' . $category->name . ' already exists',
                    DatabaseOperation::ENTITY_DUPLICATED
                );
            }

            $lastInsertedId = $this->databaseManager->insert(
                Category::TABLE_NAME,
                [
                    'name' => $category->name,
                    'slug' => Slugger::generate($category->name),
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

    public function isDuplicatedEntity(string $name, ?int $id = null): bool
    {
        $table = Category::TABLE_NAME;
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
         * @var Category|false $result
         */
        $result = $this->databaseManager->row($sql, Category::class, $params);

        return !empty($result);
    }

    public function getCategoryCloud(int $limit = 10): array
    {
        $sql = <<<SQL
            SELECT c.name, c.slug, c.id AS category_id, COUNT(ac.category_id) AS total_count
            FROM article_categories ac
             INNER JOIN categories c ON c.id = ac.category_id
            GROUP BY c.name, c.id
            ORDER BY total_count DESC
            LIMIT {$limit}
SQL;
        return $this->databaseManager->rows($sql, [], PDO::FETCH_ASSOC);
    }
}
