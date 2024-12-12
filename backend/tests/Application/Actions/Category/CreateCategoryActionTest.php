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
use Tests\TestCase;

class CreateCategoryActionTest extends TestCase
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
    public function testCreateTagSuccess(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyCreated(1);
        $repo->method('save')->willReturn($dbOp);

        $request = $this->createRequest(
            'POST',
            '/api/category'
        )->withParsedBody($this->getFakeCategory());

        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $dbOp);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testUpdateTagNotFoundException(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        $repo->method('save')->willThrowException(new CategoryNotFoundException());

        $request = $this->createRequest(
            'POST',
            '/api/category'
        )->withParsedBody($this->getFakeCategory());
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "Category not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }
}
