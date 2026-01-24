<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Article;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Domain\Article\ArticleNotFoundException;
use App\Domain\Operations\DatabaseOperation;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use Tests\ApiTestCase;

class DeleteArticleActionApiTest extends ApiTestCase
{
    public function testDeleteArticleSuccess(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted(1);
        $repo->method('delete')->willReturn($dbOp);

        $request = $this->createRequest('DELETE', '/api/article/1');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $dbOp);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testDeleteArticleNotFoundException(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        $repo->method('delete')->willThrowException(new ArticleNotFoundException());

        $request = $this->createRequest('DELETE', '/api/article/1');
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "Article not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }

    public function testDeleteArticleNotFoundWhenAffectedRowsZero(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);

        // Create a DatabaseOperation with affectedRows = 0
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted(999);
        $dbOp->affectedRows = 0;

        $repo->method('delete')->willReturn($dbOp);

        $request = $this->createRequest('DELETE', '/api/article/999');
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "Article not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }
}
