<?php

namespace Tests\Application\Actions\Tag;

use App\Application\Actions\ActionPayload;
use App\Domain\Tag\Tag;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Tests\Helper\Faker;
use Tests\TestCase;

class ListTagsActionTest extends TestCase
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

}
