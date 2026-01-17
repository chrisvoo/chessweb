<?php

namespace Tests\Infrastructure\Persistence\Article;

use App\Application\Actions\Article\Filters\ArticleFilters;
use App\Domain\Article\Article;
use App\Domain\Article\ArticleNotFoundException;
use App\Domain\Category\Category;
use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\Pagination\SortDirection;
use App\Domain\Tag\Tag;
use App\Infrastructure\Persistence\Article\ArticleRepository;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\ArticlesCategories\ArticlesCategoriesRepositoryInterface;
use App\Infrastructure\Persistence\ArticlesTags\ArticlesTagsRepositoryInterface;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tests\IntegrationTestCase;

class ArticleRepositoryTest extends IntegrationTestCase
{
    private ArticleRepositoryInterface $articleRepository;
    private ArticlesTagsRepositoryInterface|MockObject $articlesTagsRepository;
    private ArticlesCategoriesRepositoryInterface|MockObject $articlesCategoriesRepository;
    private TagRepositoryInterface|MockObject $tagRepository;
    private CategoryRepositoryInterface|MockObject $categoryRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->articlesTagsRepository = $this->createMock(ArticlesTagsRepositoryInterface::class);
        $this->tagRepository = $this->createMock(TagRepositoryInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->articlesCategoriesRepository = $this->createMock(ArticlesCategoriesRepositoryInterface::class);

        $this->articleRepository = new ArticleRepository(
            $this->container->get(DatabaseManagerInterface::class),
            $this->container->get(LoggerInterface::class),
            $this->articlesTagsRepository,
            $this->articlesCategoriesRepository,
            $this->tagRepository,
            $this->categoryRepository
        );
    }

    public function testFindById(): void
    {
        $article = $this->articleRepository->findById(2437);
        $this->assertEquals(2437, $article->id);
        $this->assertEquals(1, $article->author_id);
        $this->assertEquals('Campionato interprovinciale di Pisa e Livorno 2019', $article->title);
        $this->assertEquals('campionato-interprovinciale-di-pisa-e-livorno-2019-2437', $article->slug);
        $this->assertStringContainsString('<b>Massimo Bardi</b> (3,5/5), terzo classificato', $article->content);
        $this->assertEmpty($article->categories);
        $this->assertEmpty($article->tags);
        $this->assertEquals("2019-01-22 00:00:00", $article->created_at);
        $this->assertNull($article->updated_at);
    }

    public function testFindByIdArticleNotFound(): void
    {
        $article = $this->articleRepository->findById(9999);
        $this->assertFalse($article);
    }

    public function testFindBySlug(): void
    {
        $article = $this->articleRepository->findBySlug('campionato-interprovinciale-di-pisa-e-livorno-2019-2437');
        $this->assertEquals(2437, $article->id);
        $this->assertEquals(1, $article->author_id);
        $this->assertEquals('Campionato interprovinciale di Pisa e Livorno 2019', $article->title);
        $this->assertEquals('campionato-interprovinciale-di-pisa-e-livorno-2019-2437', $article->slug);
        $this->assertStringContainsString('<b>Massimo Bardi</b> (3,5/5), terzo classificato', $article->content);
        $this->assertEmpty($article->categories);
        $this->assertEmpty($article->tags);
        $this->assertEquals("2019-01-22 00:00:00", $article->created_at);
        $this->assertNull($article->updated_at);
    }

    public function testFindBySlugNotFound(): void
    {
        $article = $this->articleRepository->findBySlug('gnagna');
        $this->assertFalse($article);
    }

    public function testListWithDefaultFilters(): void
    {
        $filters = new ArticleFilters();
        $filters->sortOrder = SortDirection::ASC;
        $filters->sortBy = 'created_at';
        $filters->searchText = null;
        $filters->tagSlug = null;
        $filters->categorySlug = null;
        $filters->createdFrom = null;
        $filters->createdTo = null;
        $filters->skipContent = true;
        $filters->extraInfo = null;
        $filters->limit = 11;
        $filters->offset = 0;

        $articles = $this->articleRepository->list($filters);
        $this->assertCount(11, $articles);
        $this->assertEquals(2372, $articles[0]->id);
    }

    public function testListWithFilters(): void
    {
        $filters = new ArticleFilters();
        $filters->sortOrder = SortDirection::DESC;
        $filters->sortBy = 'created_at';
        $filters->searchText = "Campi Bisenzio";
        $filters->categorySlug = 'tornei';
        $filters->tagSlug = 'chess';
        $filters->createdFrom = "2025-05-01 00:00:00";
        $filters->createdTo = "2025-05-07 00:00:00";
        $filters->skipContent = false;
        $filters->extraInfo = true;
        $filters->limit = 11;
        $filters->offset = 0;

        $articles = $this->articleRepository->list($filters);
        $this->assertCount(1, $articles);
        $article = $articles[0];
        $this->assertEquals(2337, $article->id);
        $this->assertStringContainsString('Campi Bisenzio', $article->content);
        $this->assertEquals('2025-05-06 00:00:00', $article->created_at);

        $this->assertCount(1, $article->categories);
        $category = $article->categories[0];
        $this->assertEquals(103, $category->id);
        $this->assertEquals('Tornei', $category->name);
        $this->assertEquals('tornei', $category->slug);

        $this->assertCount(2, $article->tags);
        $tag = $article->tags[0];
        $this->assertEquals(1, $tag->id);
        $this->assertEquals('Chess', $tag->name);
    }

    public function testCount(): void
    {
        $filters = new ArticleFilters();
        $filters->sortOrder = SortDirection::DESC;
        $filters->sortBy = 'created_at';
        $filters->searchText = "Campi Bisenzio";
        $filters->categorySlug = 'tornei';
        $filters->tagSlug = 'chess';
        $filters->createdFrom = "2025-05-01 00:00:00";
        $filters->createdTo = "2025-05-07 00:00:00";
        $filters->skipContent = false;
        $filters->extraInfo = true;
        $filters->limit = 11;
        $filters->offset = 0;

        $count = $this->articleRepository->count($filters);
        $this->assertEquals(1, $count);
    }

    public function testUpdateRaiseExceptionWhenArticleNotFound(): void
    {
        $this->expectException(ArticleNotFoundException::class);

        $article = new Article();
        $article->id = 9999;

        $this->articleRepository->save($article);
    }

    public function testUpdateTagNotFound(): void
    {
        $this->expectException(DomainRecordNotFoundException::class);
        $this->expectExceptionMessage('Tag not found');
        $this->expectExceptionCode(DatabaseOperation::ENTITY_NOT_FOUND);

        $article = new Article();
        $article->id = 2337;
        $article->content = "<p>Hello!</p>";
        $article->title = "Updated article";
        $article->author_id = 1;

        $tag = new Tag();
        $tag->id = 9999;
        $article->tags = [$tag];

        $this->tagRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(false);

        $this->articleRepository->save($article);
    }

    public function testUpdateCategoryNotFound(): void
    {
        $this->expectException(DomainRecordNotFoundException::class);
        $this->expectExceptionMessage('Category not found');
        $this->expectExceptionCode(DatabaseOperation::ENTITY_NOT_FOUND);

        $article = new Article();
        $article->id = 2337;
        $article->content = "<p>Hello!</p>";
        $article->title = "Updated article";
        $article->author_id = 1;

        $tag = new Tag();
        $tag->id = 9999;
        $article->tags = [$tag];

        $category = new Category();
        $category->id = 9999;
        $article->categories[] = $category;

        $this->tagRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($tag);

        $this->articlesTagsRepository
            ->expects($this->once())
            ->method('deleteTagsForArticle')
            ->with($article->id);

        $this->articlesTagsRepository
            ->expects($this->once())
            ->method('saveTagsForArticle')
            ->with(
                $article->id,
                ['article_id', 'tag_id'],
                [
                    [$article->id, $tag->id]
                ]
            );

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(false);

        $this->articleRepository->save($article);
    }

    public function testUpdateSuccess(): void
    {
        $article = new Article();
        $article->id = 2437;
        $article->content = "<p>Hello!</p>";
        $article->title = "Updated article";
        $article->author_id = 1;

        $tag = new Tag();
        $tag->id = 9999;
        $article->tags = [$tag];

        $category = new Category();
        $category->id = 9999;
        $article->categories[] = $category;

        $this->tagRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($tag);

        $this->articlesTagsRepository
            ->expects($this->once())
            ->method('deleteTagsForArticle')
            ->with($article->id);

        $this->articlesTagsRepository
            ->expects($this->once())
            ->method('saveTagsForArticle')
            ->with(
                $article->id,
                ['article_id', 'tag_id'],
                [
                    [$article->id, $tag->id]
                ]
            );

        $this->articlesCategoriesRepository
            ->expects($this->once())
            ->method('deleteCategoriesForArticle')
            ->with($article->id);

        $this->articlesCategoriesRepository
            ->expects($this->once())
            ->method('saveCategoriesForArticle')
            ->with(
                $article->id,
                ['article_id', 'category_id'],
                [
                    [$article->id, $category->id]
                ]
            );

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($category);

        $op = $this->articleRepository->save($article);
        $this->assertInstanceOf(DatabaseOperation::class, $op);
        $this->assertEquals(1, $op->affectedRows);
        $this->assertEquals('Entity updated', $op->message);
        $this->assertEquals(2437, $op->entityId);

        $updatedArticle = $this->articleRepository->findById(2437);
        $this->assertEquals(2437, $updatedArticle->id);
        $this->assertEquals(1, $updatedArticle->author_id);
        $this->assertEquals("Updated article", $updatedArticle->title);
        $this->assertEquals("<p>Hello!</p>", $updatedArticle->content);
        $this->assertEquals("2019-01-22 00:00:00", $updatedArticle->created_at);
        $this->assertNotNull($updatedArticle->updated_at);
    }

    public function testInsertTagNotFound(): void
    {
        $this->expectException(DomainRecordNotFoundException::class);
        $this->expectExceptionMessage('Tag not found');
        $this->expectExceptionCode(DatabaseOperation::ENTITY_NOT_FOUND);

        $article = new Article();
        $article->content = "<p>Hello!</p>";
        $article->title = "Created article";
        $article->author_id = 1;

        $tag = new Tag();
        $tag->id = 9999;
        $article->tags = [$tag];

        $this->tagRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(false);

        $this->articleRepository->save($article);
    }

    public function testInsertCategoryNotFound(): void
    {
        $this->expectException(DomainRecordNotFoundException::class);
        $this->expectExceptionMessage('Category not found');
        $this->expectExceptionCode(DatabaseOperation::ENTITY_NOT_FOUND);

        $article = new Article();
        $article->content = "<p>Hello!</p>";
        $article->title = "Created article";
        $article->author_id = 1;

        $tag = new Tag();
        $tag->id = 9999;
        $article->tags = [$tag];

        $category = new Category();
        $category->id = 9999;
        $article->categories[] = $category;

        $this->tagRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($tag);

        $this->articlesTagsRepository
            ->expects($this->never())
            ->method('deleteTagsForArticle');

        $this->articlesTagsRepository
            ->expects($this->once())
            ->method('saveTagsForArticle');

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(false);

        $this->articleRepository->save($article);
    }

    public function testInsertSuccess(): void
    {
        $article = new Article();
        $article->content = "<p>Hello!</p>";
        $article->title = "Created article";
        $article->author_id = 1;

        $tag = new Tag();
        $tag->id = 9999;
        $article->tags = [$tag];

        $category = new Category();
        $category->id = 9999;
        $article->categories[] = $category;

        $this->tagRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($tag);

        $this->articlesTagsRepository
            ->expects($this->never())
            ->method('deleteTagsForArticle');

        $this->articlesTagsRepository
            ->expects($this->once())
            ->method('saveTagsForArticle');

        $this->articlesCategoriesRepository
            ->expects($this->never())
            ->method('deleteCategoriesForArticle');

        $this->articlesCategoriesRepository
            ->expects($this->once())
            ->method('saveCategoriesForArticle');

        $this->categoryRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn($category);

        $op = $this->articleRepository->save($article);
        $this->assertInstanceOf(DatabaseOperation::class, $op);
        $this->assertEquals(1, $op->affectedRows);
        $this->assertEquals('Entity created', $op->message);
        $this->assertNotNull($op->entityId);

        $newArticle = $this->articleRepository->findById($op->entityId);
        $this->assertEquals(1, $newArticle->author_id);
        $this->assertEquals("Created article", $newArticle->title);
        $this->assertEquals("<p>Hello!</p>", $newArticle->content);
        $this->assertStringStartsWith('created-article-', $newArticle->slug);
        $this->assertNotNull($newArticle->created_at);
        $this->assertNull($newArticle->updated_at);
    }

    public function testSaveFailure(): void
    {
        $article = new Article();
        $article->content = "<p>Hello!</p>";
        $article->title = "Created article";
        $article->author_id = 1;

        $tag = new Tag();
        $tag->id = 9999;
        $article->tags = [$tag];

        $category = new Category();
        $category->id = 9999;
        $article->categories[] = $category;

        $this->tagRepository
            ->expects($this->once())
            ->method('findById')
            ->willThrowException(new \Exception('Internal server error'));

        $op = $this->articleRepository->save($article);
        $this->assertEmpty($op->message);
        $this->assertEquals(DatabaseOperation::SERVER_ERROR, $op->code);
    }

    public function testDeleteSuccess(): void
    {
        $op = $this->articleRepository->delete(1093);
        $this->assertEquals(DatabaseOperation::ENTITY_DELETED, $op->code);
        $this->assertEquals('Entity deleted', $op->message);
    }

    public function testFindByIdWithoutExtraDetails(): void
    {
        $article = $this->articleRepository->findByIdWithExtraDetails(2337, false);
        $this->assertEquals(2337, $article->id);
        $this->assertEquals(1, $article->author_id);
        $this->assertEquals('Il Circolo promuove due squadre in serie C al CIS 2025', $article->title);
        $this->assertStringContainsString(
            '2–4 maggio 2025) al quale abbiamo preso parte con tre squadre',
            $article->content
        );
        $this->assertEmpty($article->categories);
        $this->assertEmpty($article->tags);
        $this->assertEquals('il-circolo-promuove-due-squadre-in-serie-c-al-cis-2025-2337', $article->slug);
        $this->assertEquals("2025-05-06 00:00:00", $article->created_at);
        $this->assertNull($article->updated_at);
    }

    public function testFindByIdWithExtraDetails(): void
    {
        $article = $this->articleRepository->findByIdWithExtraDetails(2337);
        $this->assertEquals(2337, $article->id);
        $this->assertEquals(1, $article->author_id);
        $this->assertEquals('Il Circolo promuove due squadre in serie C al CIS 2025', $article->title);
        $this->assertStringContainsString('2–4 maggio 2025) al quale abbiamo preso parte con tre squadre', $article->content);
        $this->assertEquals('il-circolo-promuove-due-squadre-in-serie-c-al-cis-2025-2337', $article->slug);

        $category = $article->categories[0];
        $this->assertEquals(103, $category->id);
        $this->assertEquals('Tornei', $category->name);

        $this->assertCount(2, $article->tags);
        $tag = $article->tags[0];
        $this->assertEquals(1, $tag->id);
        $this->assertEquals('Chess', $tag->name);

        $this->assertEquals("2025-05-06 00:00:00", $article->created_at);
        $this->assertNull($article->updated_at);
    }

    public function testFindByIdWithExtraDetailsArticleNotFound(): void
    {
        $article = $this->articleRepository->findByIdWithExtraDetails(9999);
        $this->assertFalse($article);
    }

    public function testFindBySlugWithoutExtraDetails(): void
    {
        $article = $this->articleRepository->findBySlugWithExtraDetails(
            'il-circolo-promuove-due-squadre-in-serie-c-al-cis-2025-2337',
            false
        );
        $this->assertEquals(2337, $article->id);
        $this->assertEquals(1, $article->author_id);
        $this->assertEquals('Il Circolo promuove due squadre in serie C al CIS 2025', $article->title);
        $this->assertStringContainsString(
            '2–4 maggio 2025) al quale abbiamo preso parte con tre squadre',
            $article->content
        );
        $this->assertEmpty($article->categories);
        $this->assertEmpty($article->tags);
        $this->assertEquals('il-circolo-promuove-due-squadre-in-serie-c-al-cis-2025-2337', $article->slug);
        $this->assertEquals("2025-05-06 00:00:00", $article->created_at);
        $this->assertNull($article->updated_at);
    }

    public function testFindBySlugWithExtraDetails(): void
    {
        $article = $this->articleRepository->findBySlugWithExtraDetails(
            'il-circolo-promuove-due-squadre-in-serie-c-al-cis-2025-2337'
        );
        $this->assertEquals(2337, $article->id);
        $this->assertEquals(1, $article->author_id);
        $this->assertEquals('Il Circolo promuove due squadre in serie C al CIS 2025', $article->title);
        $this->assertStringContainsString(
            '2–4 maggio 2025) al quale abbiamo preso parte con tre squadre',
            $article->content
        );
        $this->assertEquals('il-circolo-promuove-due-squadre-in-serie-c-al-cis-2025-2337', $article->slug);

        $category = $article->categories[0];
        $this->assertEquals(103, $category->id);
        $this->assertEquals('Tornei', $category->name);

        $this->assertCount(2, $article->tags);
        $tag = $article->tags[0];
        $this->assertEquals(1, $tag->id);
        $this->assertEquals('Chess', $tag->name);

        $this->assertEquals("2025-05-06 00:00:00", $article->created_at);
        $this->assertNull($article->updated_at);
    }

    public function testFindBySlugWithExtraDetailsArticleNotFound(): void
    {
        $article = $this->articleRepository->findBySlugWithExtraDetails("slug-not-found");
        $this->assertFalse($article);
    }
}
