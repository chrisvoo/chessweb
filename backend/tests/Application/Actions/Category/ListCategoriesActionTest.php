<?php

namespace Tests\Application\Actions\Category;

use App\Application\Actions\ActionPayload;
use App\Domain\Category\Category;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use Tests\Helper\Faker;
use Tests\TestCase;

class ListCategoriesActionTest extends TestCase
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

}
