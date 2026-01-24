<?php

namespace Tests\Application\Actions\Tag;

use App\Application\Actions\ActionPayload;
use App\Domain\Tag\Tag;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Tests\Helper\Faker;
use Tests\ApiTestCase;

class ListTagsActionApiTest extends ApiTestCase
{
    public function testListTagsSuccess(): void
    {
        $repo = $this->mockRepository(TagRepositoryInterface::class);
        for ($i = 0; $i < 7; $i++) {
            $tags[] = Faker::fakeData(Tag::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(7);
        $repo->method('list')->withAnyParameters()->willReturn($tags);

        $request = $this->createRequest(
            'GET',
            '/api/tags',
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

    public function testListTagsWithSortOrderDesc(): void
    {
        $repo = $this->mockRepository(TagRepositoryInterface::class);
        $tags = [];
        for ($i = 0; $i < 5; $i++) {
            $tags[] = Faker::fakeData(Tag::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(5);
        $repo->method('list')->withAnyParameters()->willReturn($tags);

        $request = $this->createRequest(
            'GET',
            '/api/tags',
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
                'items' => $tags,
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

    public function testListTagsWithSortOrderAsc(): void
    {
        $repo = $this->mockRepository(TagRepositoryInterface::class);
        $tags = [];
        for ($i = 0; $i < 3; $i++) {
            $tags[] = Faker::fakeData(Tag::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(3);
        $repo->method('list')->withAnyParameters()->willReturn($tags);

        $request = $this->createRequest(
            'GET',
            '/api/tags',
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
                'items' => $tags,
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

    public function testListTagsWithAllItems(): void
    {
        $repo = $this->mockRepository(TagRepositoryInterface::class);
        $tags = [];
        for ($i = 0; $i < 15; $i++) {
            $tags[] = Faker::fakeData(Tag::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(15);
        $repo->method('list')->withAnyParameters()->willReturn($tags);

        $request = $this->createRequest(
            'GET',
            '/api/tags',
            http_build_query([
                'all_items' => true
            ])
        );
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(
            200,
            [
                'items' => $tags
            ]
        );
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
