<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Article;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Domain\Article\Article;
use App\Domain\Article\ArticleNotFoundException;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\Tag\Tag;
use App\Domain\Tag\TagNotFoundException;
use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Tests\Helper\Faker;
use Tests\ApiTestCase;

class UpdateArticleActionApiTest extends ApiTestCase
{
    private function getFakeArticle(): array
    {
        $article = Faker::fakeData(Article::class)->jsonSerialize();
        unset($article['created_at']);
        unset($article['updated_at']);
        unset($article['id']);
        unset($article['tags']);
        unset($article['categories']);

        return $article;
    }

    /**
     * @throws \ReflectionException
     */
    public function testUpdateArticleSuccess(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated(1);
        $repo->method('save')->willReturn($dbOp);

        $request = $this->createRequest(
            'PUT',
            '/api/article/1'
        )->withParsedBody($this->getFakeArticle());

        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $dbOp);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testUpdateTagNotFoundException(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $repo->method('save')->willThrowException(new ArticleNotFoundException());

        $request = $this->createRequest(
            'PUT',
            '/api/article/1'
        )->withParsedBody($this->getFakeArticle());
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "Article not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }
}
