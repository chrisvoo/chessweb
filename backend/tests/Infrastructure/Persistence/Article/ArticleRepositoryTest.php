<?php

namespace Tests\Infrastructure\Persistence\Article;

use App\Application\Actions\Article\Filters\ArticleFilters;
use App\Domain\Pagination\SortDirection;
use App\Infrastructure\Persistence\Article\ArticleRepository;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\ArticlesCategories\ArticlesCategoriesRepositoryInterface;
use App\Infrastructure\Persistence\ArticlesTags\ArticlesTagsRepositoryInterface;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Psr\Log\LoggerInterface;
use Tests\IntegrationTestCase;

class ArticleRepositoryTest extends IntegrationTestCase
{
    private ArticleRepositoryInterface $articleRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->articleRepository = new ArticleRepository(
            $this->container->get(DatabaseManagerInterface::class),
            $this->container->get(LoggerInterface::class),
            $this->container->get(ArticlesTagsRepositoryInterface::class),
            $this->container->get(ArticlesCategoriesRepositoryInterface::class),
            $this->container->get(TagRepositoryInterface::class),
            $this->container->get(CategoryRepositoryInterface::class),
        );
    }

    public function testFindById(): void
    {
        $article = $this->articleRepository->findById(1093);
        $this->assertEquals(1093, $article->id);
        $this->assertEquals(1, $article->author_id);
        $this->assertEquals('new', $article->title);
        $this->assertStringContainsString('<p>hih oh</p><p><img src="data:image/jpeg', $article->content);
        $this->assertEmpty($article->categories);
        $this->assertEmpty($article->tags);
        $this->assertEquals("2025-12-30 16:26:42", $article->created_at);
        $this->assertNull($article->updated_at);
    }

    public function testFindByIdArticleNotFound(): void
    {
        $article = $this->articleRepository->findById(9999);
        $this->assertFalse($article);
    }

    public function testListWithDefaultFilters(): void
    {
        $filters = new ArticleFilters();
        $filters->sortOrder = SortDirection::ASC;
        $filters->sortBy = 'created_at';
        $filters->searchText = null;
        $filters->categoryId = null;
        $filters->tagId = null;
        $filters->createdFrom = null;
        $filters->createdTo = null;
        $filters->skipContent = true;
        $filters->extraInfo = null;
        $filters->limit = 11;
        $filters->offset = 0;

        $articles = $this->articleRepository->list($filters);
        $this->assertCount(11, $articles);
        $this->assertEquals(1000, $articles[0]->id);
    }

    public function testListWithFilters(): void
    {
        $filters = new ArticleFilters();
        $filters->sortOrder = SortDirection::DESC;
        $filters->sortBy = 'created_at';
        $filters->searchText = "Campi Bisenzio";
        $filters->categoryId = 44;
        $filters->tagId = 1;
        $filters->createdFrom = "2025-05-01 00:00:00";
        $filters->createdTo = "2025-05-07 00:00:00";
        $filters->skipContent = false;
        $filters->extraInfo = true;
        $filters->limit = 11;
        $filters->offset = 0;

        $articles = $this->articleRepository->list($filters);
        $this->assertCount(1, $articles);
        $article = $articles[0];
        $this->assertEquals(967, $article->id);
        $this->assertStringContainsString('Campi Bisenzio', $article->content);
        $this->assertEquals('2025-05-06 00:00:00', $article->created_at);

        $category = $article->categories[0];
        $this->assertEquals(44, $category->id);
        $this->assertEquals('Tornei', $category->name);

        $this->assertCount(2, $article->tags);
        $tag = $article->tags[0];
        $this->assertEquals(1, $tag->id);
        $this->assertEquals('chess', $tag->name);
    }

    public function testCount(): void
    {
        $filters = new ArticleFilters();
        $filters->sortOrder = SortDirection::DESC;
        $filters->sortBy = 'created_at';
        $filters->searchText = "Campi Bisenzio";
        $filters->categoryId = 44;
        $filters->tagId = 1;
        $filters->createdFrom = "2025-05-01 00:00:00";
        $filters->createdTo = "2025-05-07 00:00:00";
        $filters->skipContent = false;
        $filters->extraInfo = true;
        $filters->limit = 11;
        $filters->offset = 0;

        $count = $this->articleRepository->count($filters);
        $this->assertEquals(1, $count);
    }
}
