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
    private function getFakeTag(): array
    {
        $tag = Faker::fakeData(Category::class)->jsonSerialize();
        unset($tag['created_at']);
        unset($tag['updated_at']);
        unset($tag['id']);

        return $tag;
    }

    /**
     * @throws \ReflectionException
     */
    public function testUpdateTagSuccess(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated(1);
        $repo->method('save')->willReturn($dbOp);

        $request = $this->createRequest(
            'PUT',
            '/api/category/1'
        )->withParsedBody($this->getFakeTag());

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
            'PUT',
            '/api/category/1'
        )->withParsedBody($this->getFakeTag());
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "Category not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }
}
