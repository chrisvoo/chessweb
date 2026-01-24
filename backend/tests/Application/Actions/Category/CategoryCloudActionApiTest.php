<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Category;

use App\Application\Actions\ActionPayload;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use Tests\ApiTestCase;

class CategoryCloudActionApiTest extends ApiTestCase
{
    public function testCategoryCloudSuccess(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        
        $cloudData = [
            ['name' => 'PHP', 'slug' => 'php', 'category_id' => 1, 'total_count' => 15],
            ['name' => 'JavaScript', 'slug' => 'javascript', 'category_id' => 2, 'total_count' => 10],
            ['name' => 'Python', 'slug' => 'python', 'category_id' => 3, 'total_count' => 8],
        ];
        
        $repo->method('getCategoryCloud')->willReturn($cloudData);

        $request = $this->createRequest('GET', '/api/categories/stats');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, ['items' => $cloudData]);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testCategoryCloudEmpty(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        $repo->method('getCategoryCloud')->willReturn([]);

        $request = $this->createRequest('GET', '/api/categories/stats');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, ['items' => []]);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
