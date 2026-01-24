<?php

namespace Tests\Application\Actions\Category;

use App\Application\Actions\ActionPayload;
use App\Domain\Category\Category;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use Tests\Helper\Faker;
use Tests\ApiTestCase;

class ListCategoriesActionApiTest extends ApiTestCase
{
    public function testListCategoriesSuccess(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        for ($i = 0; $i < 7; $i++) {
            $tags[] = Faker::fakeData(Category::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(7);
        $repo->method('list')->withAnyParameters()->willReturn($tags);

        $request = $this->createRequest(
            'GET',
            '/api/categories',
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
                'items' => array_slice($tags, 0, 3),
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

    public function testListCategoriesWithSortOrderDesc(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        $categories = [];
        for ($i = 0; $i < 5; $i++) {
            $categories[] = Faker::fakeData(Category::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(5);
        $repo->method('list')->withAnyParameters()->willReturn($categories);

        $request = $this->createRequest(
            'GET',
            '/api/categories',
            http_build_query([
                'page' => 1,
                'page_size' => 10,
                'sort_order' => 'desc'
            ])
        );
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(
            200,
            [
                'items' => $categories,
                'total_items' => 5,
                'total_pages' => 1,
                'has_more_items' => false,
                'page' => 1,
                'page_size' => 10
            ]
        );
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testListCategoriesWithSortOrderAsc(): void
    {
        $repo = $this->mockRepository(CategoryRepositoryInterface::class);
        $categories = [];
        for ($i = 0; $i < 3; $i++) {
            $categories[] = Faker::fakeData(Category::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(3);
        $repo->method('list')->withAnyParameters()->willReturn($categories);

        $request = $this->createRequest(
            'GET',
            '/api/categories',
            http_build_query([
                'page' => 1,
                'page_size' => 10,
                'sort_order' => 'asc'
            ])
        );
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(
            200,
            [
                'items' => $categories,
                'total_items' => 3,
                'total_pages' => 1,
                'has_more_items' => false,
                'page' => 1,
                'page_size' => 10
            ]
        );
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
