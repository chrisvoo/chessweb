<?php

namespace Tests\Infrastructure\Persistence;

use App\Domain\Tag\Tag;
use App\Infrastructure\Persistence\DatabaseManager;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Tests\IntegrationTestCase;

class DatabaseManagerTest extends IntegrationTestCase
{
    private DatabaseManagerInterface $databaseManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseManager = $this->container->get(DatabaseManagerInterface::class);
    }

    public function testConnect(): void
    {
        $dm = new DatabaseManager(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4;port={$_ENV['DB_PORT']}",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            $this->container->get(LoggerInterface::class),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $result = $dm->connect();
        $this->assertInstanceOf(DatabaseManagerInterface::class, $result);
    }

    public function testGetPdo(): void
    {
        $pdo = $this->databaseManager->getPdo();
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    public function testRaw(): void
    {
        $result = $this->databaseManager->raw('SELECT 1 as test');
        $this->assertInstanceOf(PDOStatement::class, $result);

        $row = $result->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, $row['test']);
    }

    public function testRunWithoutParams(): void
    {
        $result = $this->databaseManager->run('SELECT id, name FROM tags LIMIT 1');
        $this->assertInstanceOf(PDOStatement::class, $result);

        $row = $result->fetch(PDO::FETCH_ASSOC);
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
    }

    public function testRunWithParams(): void
    {
        $result = $this->databaseManager->run(
            'SELECT id, name FROM tags WHERE id = :id',
            ['id' => 1]
        );
        $this->assertInstanceOf(PDOStatement::class, $result);

        $row = $result->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('Chess', $row['name']);
    }

    public function testRowsWithFetchClass(): void
    {
        $rows = $this->databaseManager->rows(
            'SELECT id, name, slug, created_at, updated_at FROM tags',
            [],
            PDO::FETCH_CLASS,
            Tag::class
        );

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertContainsOnlyInstancesOf(Tag::class, $rows);
    }

    public function testRowsWithFetchAssoc(): void
    {
        $rows = $this->databaseManager->rows(
            'SELECT id, name FROM tags',
            [],
            PDO::FETCH_ASSOC
        );

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('name', $rows[0]);
    }

    public function testRowsWithParams(): void
    {
        $rows = $this->databaseManager->rows(
            'SELECT id, name, slug, created_at, updated_at FROM tags WHERE name LIKE :name',
            ['name' => '%Chess%'],
            PDO::FETCH_CLASS,
            Tag::class
        );

        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);
        $this->assertEquals('Chess', $rows[0]->name);
    }

    public function testRow(): void
    {
        $row = $this->databaseManager->row(
            'SELECT id, name, slug, created_at, updated_at FROM tags WHERE id = :id',
            Tag::class,
            ['id' => 1]
        );

        $this->assertInstanceOf(Tag::class, $row);
        $this->assertEquals(1, $row->id);
        $this->assertEquals('Chess', $row->name);
    }

    public function testRowNotFound(): void
    {
        $row = $this->databaseManager->row(
            'SELECT id, name, slug, created_at, updated_at FROM tags WHERE id = :id',
            Tag::class,
            ['id' => 99999]
        );

        $this->assertFalse($row);
    }

    public function testGetById(): void
    {
        $row = $this->databaseManager->getById('tags', 1, PDO::FETCH_ASSOC);

        $this->assertIsArray($row);
        $this->assertEquals(1, $row['id']);
        $this->assertEquals('Chess', $row['name']);
    }

    public function testGetByIdNotFound(): void
    {
        $row = $this->databaseManager->getById('tags', 99999, PDO::FETCH_ASSOC);

        $this->assertFalse($row);
    }

    public function testCount(): void
    {
        $count = $this->databaseManager->count('SELECT id FROM tags');

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testCountWithParams(): void
    {
        $count = $this->databaseManager->count(
            'SELECT id FROM tags WHERE name LIKE :name',
            ['name' => '%Chess%']
        );

        $this->assertEquals(1, $count);
    }

    public function testInsertAndLastInsertId(): void
    {
        $lastId = $this->databaseManager->insert('tags', [
            'name' => 'TestInsertTag',
            'slug' => 'test-insert-tag',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->assertNotFalse($lastId);
        $this->assertIsString($lastId);
        $this->assertGreaterThan(0, (int)$lastId);

        // Verify the insert
        $row = $this->databaseManager->getById('tags', (int)$lastId, PDO::FETCH_ASSOC);
        $this->assertEquals('TestInsertTag', $row['name']);
    }

    public function testInsertWithBacktickColumns(): void
    {
        // Test that columns with backticks are handled correctly
        $lastId = $this->databaseManager->insert('tags', [
            '`name`' => 'TestBacktickTag',
            'slug' => 'test-backtick-tag',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->assertNotFalse($lastId);

        $row = $this->databaseManager->getById('tags', (int)$lastId, PDO::FETCH_ASSOC);
        $this->assertEquals('TestBacktickTag', $row['name']);
    }

    public function testBatchInsert(): void
    {
        $fields = ['name', 'slug', 'created_at'];
        $data = [
            ['BatchTag1', 'batch-tag-1', date('Y-m-d H:i:s')],
            ['BatchTag2', 'batch-tag-2', date('Y-m-d H:i:s')],
            ['BatchTag3', 'batch-tag-3', date('Y-m-d H:i:s')],
        ];

        $stmt = $this->databaseManager->batchInsert('tags', $fields, $data);

        $this->assertInstanceOf(PDOStatement::class, $stmt);
        $this->assertEquals(3, $stmt->rowCount());
    }

    public function testBatchInsertEmptyFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("You must pass the table's fields for the batch insert");

        $this->databaseManager->batchInsert('tags', [], [['data']]);
    }

    public function testBatchInsertEmptyData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("You must pass the data for the batch insert");

        $this->databaseManager->batchInsert('tags', ['name'], []);
    }

    public function testUpdate(): void
    {
        // First insert a record to update
        $lastId = $this->databaseManager->insert('tags', [
            'name' => 'TagToUpdate',
            'slug' => 'tag-to-update',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $affectedRows = $this->databaseManager->update(
            'tags',
            ['name' => 'UpdatedTagName', 'slug' => 'updated-tag-name'],
            ['id' => (int)$lastId]
        );

        $this->assertEquals(1, $affectedRows);

        // Verify the update
        $row = $this->databaseManager->getById('tags', (int)$lastId, PDO::FETCH_ASSOC);
        $this->assertEquals('UpdatedTagName', $row['name']);
        $this->assertEquals('updated-tag-name', $row['slug']);
    }

    public function testUpdateMultipleWhereConditions(): void
    {
        // Insert a record
        $lastId = $this->databaseManager->insert('tags', [
            'name' => 'MultiWhereTag',
            'slug' => 'multi-where-tag',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $affectedRows = $this->databaseManager->update(
            'tags',
            ['name' => 'UpdatedMultiWhere'],
            ['id' => (int)$lastId, 'slug' => 'multi-where-tag']
        );

        $this->assertEquals(1, $affectedRows);
    }

    public function testUpdateNoMatchingRows(): void
    {
        $affectedRows = $this->databaseManager->update(
            'tags',
            ['name' => 'NoMatch'],
            ['id' => 99999]
        );

        $this->assertEquals(0, $affectedRows);
    }

    public function testDelete(): void
    {
        // Insert a record to delete
        $lastId = $this->databaseManager->insert('tags', [
            'name' => 'TagToDelete',
            'slug' => 'tag-to-delete',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $affectedRows = $this->databaseManager->delete(
            'tags',
            ['id' => (int)$lastId]
        );

        $this->assertEquals(1, $affectedRows);

        // Verify deletion
        $row = $this->databaseManager->getById('tags', (int)$lastId, PDO::FETCH_ASSOC);
        $this->assertFalse($row);
    }

    public function testDeleteWithMultipleConditions(): void
    {
        // Insert a record
        $lastId = $this->databaseManager->insert('tags', [
            'name' => 'MultiCondDelete',
            'slug' => 'multi-cond-delete',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $affectedRows = $this->databaseManager->delete(
            'tags',
            ['id' => (int)$lastId, 'slug' => 'multi-cond-delete']
        );

        $this->assertEquals(1, $affectedRows);
    }

    public function testDeleteNoMatch(): void
    {
        $affectedRows = $this->databaseManager->delete(
            'tags',
            ['id' => 99999]
        );

        $this->assertEquals(0, $affectedRows);
    }

    public function testDeleteById(): void
    {
        // Insert a record to delete
        $lastId = $this->databaseManager->insert('tags', [
            'name' => 'DeleteByIdTag',
            'slug' => 'delete-by-id-tag',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $affectedRows = $this->databaseManager->deleteById('tags', (int)$lastId);

        $this->assertEquals(1, $affectedRows);

        // Verify deletion
        $row = $this->databaseManager->getById('tags', (int)$lastId, PDO::FETCH_ASSOC);
        $this->assertFalse($row);
    }

    public function testDeleteByIdNoMatch(): void
    {
        $affectedRows = $this->databaseManager->deleteById('tags', 99999);

        $this->assertEquals(0, $affectedRows);
    }

    public function testDeleteByIds(): void
    {
        // Insert multiple records
        $id1 = $this->databaseManager->insert('tags', [
            'name' => 'DeleteByIds1',
            'slug' => 'delete-by-ids-1',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $id2 = $this->databaseManager->insert('tags', [
            'name' => 'DeleteByIds2',
            'slug' => 'delete-by-ids-2',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $ids = "$id1,$id2";
        $affectedRows = $this->databaseManager->deleteByIds('tags', 'id', $ids);

        $this->assertEquals(2, $affectedRows);
    }

    /**
     * Note: Transaction methods (startTransaction, commit, rollback) cannot be fully tested
     * because IntegrationTestCase wraps each test in a transaction for database isolation,
     * and DbTestManager stubs these methods to avoid nested transaction issues.
     *
     * These tests only verify that the interface contract is satisfied (methods return bool).
     * Real transaction behavior is tested implicitly through the test framework itself.
     */
    public function testTransactionMethodsReturnBool(): void
    {
        $this->assertIsBool($this->databaseManager->startTransaction());
        $this->assertIsBool($this->databaseManager->commit());
        $this->assertIsBool($this->databaseManager->rollback());
    }

    public function testLastInsertId(): void
    {
        $this->databaseManager->insert('tags', [
            'name' => 'LastInsertIdTag',
            'slug' => 'last-insert-id-tag',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $lastId = $this->databaseManager->lastInsertId();

        $this->assertNotFalse($lastId);
        $this->assertIsString($lastId);
        $this->assertGreaterThan(0, (int)$lastId);
    }
}
