<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Article;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\Tag\TagNotFoundException;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Tests\ApiTestCase;

class DeleteArticleActionApiTest extends ApiTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testGetSingleUserSuccess(): void
    {
        $repo = $this->mockRepository(TagRepositoryInterface::class);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted(1);
        $repo->method('delete')->willReturn($dbOp);

        $request = $this->createRequest('DELETE', '/api/tag/1');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $dbOp);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testGetSingleUserNotFoundException(): void
    {
        $repo = $this->mockRepository(TagRepositoryInterface::class);
        $repo->method('delete')->willThrowException(new TagNotFoundException());

        $request = $this->createRequest('DELETE', '/api/tag/1');
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "Tag not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }
}
