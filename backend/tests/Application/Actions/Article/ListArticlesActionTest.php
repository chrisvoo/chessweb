<?php

namespace Tests\Application\Actions\Article;

use App\Application\Actions\ActionPayload;
use App\Domain\Article\Article;
use App\Domain\Tag\Tag;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Tests\Helper\Faker;
use Tests\TestCase;

class ListArticlesActionTest extends TestCase
{
    public function testListTagsSuccess(): void
    {
        $repo = $this->mockRepository(ArticleRepositoryInterface::class);
        for ($i = 0; $i < 7; $i++) {
            $articles[] = Faker::fakeData(Article::class);
        }

        $repo->method('count')->withAnyParameters()->willReturn(7);
        $repo->method('list')->withAnyParameters()->willReturn($articles);

        $request = $this->createRequest(
            'GET',
            '/api/articles',
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
                'items' => array_slice($articles, 0, 3),
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
