<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Category;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Domain\Category\Category;
use App\Domain\Category\CategoryNotFoundException;
use App\Domain\Operations\DatabaseOperation;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use Tests\Helper\Faker;
use Tests\ApiTestCase;

class UpdateCategoryActionApiTest extends ApiTestCase
{
    private function getFakeCategory(): array
    {
        $category = Faker::fakeData(Category::class)->jsonSerialize();
        unset($category['created_at']);
        unset($category['updated_at']);
        unset($category['id']);

        return $category;
    }

    /**
     * @throws \ReflectionException
     */
    public function testUpdateCategorySuccess(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated(1);
        $repo->method('save')->willReturn($dbOp);

        $request = $this->createRequest(
            'PUT',
            '/api/category/1'
        )->withParsedBody($this->getFakeCategory());

        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $dbOp);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testUpdateCategoryNotFoundException(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        $repo->method('save')->willThrowException(new CategoryNotFoundException());

        $request = $this->createRequest(
            'PUT',
            '/api/category/1'
        )->withParsedBody($this->getFakeCategory());
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "Category not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }

    public function testUpdateCategoryFailsWhenOperationNotSuccessful(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);

        // Create a failed DatabaseOperation (e.g., duplicate entity)
        $dbOp = DatabaseOperation::failed(
            'Category Test already exists',
            DatabaseOperation::ENTITY_DUPLICATED
        );

        $repo->method('save')->willReturn($dbOp);

        $request = $this->createRequest(
            'PUT',
            '/api/category/1'
        )->withParsedBody($this->getFakeCategory());

        $response = $this->app->handle($request);
        $payload = json_decode((string) $response->getBody(), true);

        $this->assertEquals(400, $payload['statusCode']);
        $this->assertEquals([
            'success' => false,
            'message' => 'Category Test already exists',
            'code' => DatabaseOperation::ENTITY_DUPLICATED
        ], $payload['data']);
        $this->assertEquals([
            'type' => ActionError::BAD_REQUEST,
            'description' => 'Category Test already exists'
        ], $payload['error']);
    }
}
