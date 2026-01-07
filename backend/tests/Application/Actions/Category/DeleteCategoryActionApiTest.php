<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Category;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Domain\Category\CategoryNotFoundException;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\Tag\TagNotFoundException;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Tests\ApiTestCase;

class DeleteCategoryActionApiTest extends ApiTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testGetSingleUserSuccess(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted(1);
        $repo->method('delete')->willReturn($dbOp);

        $request = $this->createRequest('DELETE', '/api/category/1');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $dbOp);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testGetSingleUserNotFoundException(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        $repo->method('delete')->willThrowException(new CategoryNotFoundException());

        $request = $this->createRequest('DELETE', '/api/category/1');
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "Category not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }
}
