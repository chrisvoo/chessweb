<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Article;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Domain\Article\Article;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use Tests\ApiTestCase;
use Tests\Helper\Faker;

class ViewSingleArticleActionApiTest extends ApiTestCase
{
    public function testViewArticleByIdSuccess(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $article = Faker::fakeData(Article::class);
        $article->id = 123;

        $repo->method('findByIdWithExtraDetails')->willReturn($article);

        $request = $this->createRequest('GET', '/api/article/123');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $article);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testViewArticleBySlugSuccess(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $article = Faker::fakeData(Article::class);
        $article->slug = 'my-article-slug';

        $repo->method('findBySlugWithExtraDetails')->willReturn($article);

        $request = $this->createRequest('GET', '/api/article/my-article-slug');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $article);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testViewArticleByIdNotFound(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $repo->method('findByIdWithExtraDetails')->willReturn(false);

        $request = $this->createRequest('GET', '/api/article/999');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, 'Article not found');
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testViewArticleBySlugNotFound(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $repo->method('findBySlugWithExtraDetails')->willReturn(false);

        $request = $this->createRequest('GET', '/api/article/non-existent-slug');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, 'Article not found');
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testViewArticleWithExtraInfo(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $article = Faker::fakeData(Article::class);
        $article->id = 123;

        $repo->method('findByIdWithExtraDetails')
            ->with(123, true)
            ->willReturn($article);

        $request = $this->createRequest(
            'GET',
            '/api/article/123',
            'extra_info=true'
        );
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testViewArticleWithExtraInfoNumeric(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $article = Faker::fakeData(Article::class);
        $article->id = 123;

        $repo->method('findByIdWithExtraDetails')
            ->with(123, true)
            ->willReturn($article);

        $request = $this->createRequest(
            'GET',
            '/api/article/123',
            'extra_info=1'
        );
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
