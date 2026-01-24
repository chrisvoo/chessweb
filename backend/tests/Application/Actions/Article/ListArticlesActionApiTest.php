<?php

namespace Tests\Application\Actions\Article;

use App\Application\Actions\ActionPayload;
use App\Domain\Article\Article;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use Tests\Helper\Faker;
use Tests\ApiTestCase;

class ListArticlesActionApiTest extends ApiTestCase
{
    public function testListArticlesSuccess(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $articles = [];
        for ($i = 0; $i < 7; $i++) {
            $articles[] = Faker::fakeData(Article::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(7);
        $repo->method('list')->withAnyParameters()->willReturn($articles);

        $request = $this->createRequest(
            'GET',
            '/api/articles',
            http_build_query([
                'page' => 1,
                'page_size' => 3
            ])
        );
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(
            200,
            [
                'items' => array_slice($articles, 0, 3),
                'total_items' => 7,
                'total_pages' => 3,
                'has_more_items' => true,
                'page' => 1,
                'page_size' => 3
            ]
        );
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testListArticlesWithSortOrderAsc(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $articles = [];
        for ($i = 0; $i < 3; $i++) {
            $articles[] = Faker::fakeData(Article::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(3);
        $repo->method('list')->withAnyParameters()->willReturn($articles);

        $request = $this->createRequest(
            'GET',
            '/api/articles',
            http_build_query([
                'page' => 1,
                'page_size' => 10,
                'sort_order' => 'asc'
            ])
        );
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('items', $payload['data']);
    }

    public function testListArticlesWithSortOrderDesc(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $articles = [];
        for ($i = 0; $i < 3; $i++) {
            $articles[] = Faker::fakeData(Article::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(3);
        $repo->method('list')->withAnyParameters()->willReturn($articles);

        $request = $this->createRequest(
            'GET',
            '/api/articles',
            http_build_query([
                'page' => 1,
                'page_size' => 10,
                'sort_order' => 'desc',
                'sort_by' => 'created_at'
            ])
        );
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('items', $payload['data']);
    }

    public function testListArticlesWithAllFilters(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $articles = [];
        for ($i = 0; $i < 2; $i++) {
            $articles[] = Faker::fakeData(Article::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(2);
        $repo->method('list')->withAnyParameters()->willReturn($articles);

        $request = $this->createRequest(
            'GET',
            '/api/articles',
            http_build_query([
                'page' => 1,
                'page_size' => 10,
                'sort_order' => 'desc',
                'sort_by' => 'title',
                'search_text' => 'chess',
                'skip_content' => 'false',
                'extra_info' => 'true'
            ])
        );
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
