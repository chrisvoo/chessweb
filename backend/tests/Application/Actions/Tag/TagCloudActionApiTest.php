<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Tag;

use App\Application\Actions\ActionPayload;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Tests\ApiTestCase;

class TagCloudActionApiTest extends ApiTestCase
{
    public function testTagCloudSuccess(): void
    {
        $repo = $this->mockRepository(TagRepositoryInterface::class);

        $cloudData = [
            ['name' => 'PHP', 'slug' => 'php', 'tag_id' => 1, 'total_count' => 25],
            ['name' => 'JavaScript', 'slug' => 'javascript', 'tag_id' => 2, 'total_count' => 18],
            ['name' => 'Python', 'slug' => 'python', 'tag_id' => 3, 'total_count' => 12],
        ];

        $repo->method('getTagCloud')->willReturn($cloudData);

        $request = $this->createRequest('GET', '/api/tags/stats');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, ['items' => $cloudData]);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testTagCloudEmpty(): void
    {
        $repo = $this->mockRepository(TagRepositoryInterface::class);
        $repo->method('getTagCloud')->willReturn([]);

        $request = $this->createRequest('GET', '/api/tags/stats');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, ['items' => []]);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
