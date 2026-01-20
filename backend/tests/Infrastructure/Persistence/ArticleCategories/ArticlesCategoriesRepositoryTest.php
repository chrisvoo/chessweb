<?php

namespace Tests\Infrastructure\Persistence\ArticleCategories;

use App\Domain\ArticlesCategories\ArticlesCategories;
use App\Domain\Operations\DatabaseOperation;
use App\Infrastructure\Persistence\ArticlesCategories\ArticlesCategoriesRepository;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ArticlesCategoriesRepositoryTest extends TestCase
{
    private DatabaseManagerInterface|MockObject $database;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = $this->createMock(DatabaseManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testDeleteCategoriesForArticle(): void
    {
        $articleId = 1;

        $this->database
            ->expects($this->once())
            ->method('delete')
            ->with(
                ArticlesCategories::TABLE_NAME,
                ['article_id' => $articleId]
            )
            ->willReturn(1);

        $repo = new ArticlesCategoriesRepository($this->database, $this->logger);
        $op = $repo->deleteCategoriesForArticle($articleId);
        $this->assertNull($op->entityId);
        $this->assertEquals("Categories for article $articleId have been deleted.", $op->message);
        $this->assertEquals(DatabaseOperation::ENTITY_DELETED, $op->code);
        $this->assertEquals(1, $op->affectedRows);
    }

    public function testSaveCategoriesForArticle(): void
    {
        $articleId = 1;
        $fields = ['article_id', 'category_id'];
        $rows = [[$articleId, 1], [$articleId, 2]];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('rowCount')->willReturn(count($rows));

        $this->database
            ->expects($this->once())
            ->method('batchInsert')
            ->with(ArticlesCategories::TABLE_NAME, $fields, $rows)
            ->willReturn($stmt);

        $repo = new ArticlesCategoriesRepository($this->database, $this->logger);
        $op = $repo->saveCategoriesForArticle($articleId, $fields, $rows);
        $this->assertNull($op->entityId);
        $this->assertEquals("Categories for article $articleId have been saved.", $op->message);
        $this->assertEquals(DatabaseOperation::ENTITY_CREATED, $op->code);
        $this->assertEquals(count($rows), $op->affectedRows);
    }
}
