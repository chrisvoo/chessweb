<?php

namespace Tests\Domain\Article;

use App\Domain\Article\Article;
use Tests\ApiTestCase;

class ArticleApiTest extends ApiTestCase
{
    public function testJsonSerialize(): void
    {
        $id = 1;
        $title = "A good article";
        $content = "<h1>Content</h1>";
        $createdAt = '2021-01-01 00:00:00';
        $updatedAt = '2021-01-01 00:00:00';

        $article = new Article();
        $article->id = $id;
        $article->title = $title;
        $article->content = $content;
        $article->author_id = $id;
        $article->created_at = $createdAt;
        $article->updated_at = $updatedAt;

        $expectedPayload = json_encode([
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'author_id' => $id,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        $this->assertEquals($expectedPayload, json_encode($article));
    }

    public function testSortableFields(): void
    {
        $this->assertSame(
            [
                'id',
                'title',
                'author_id',
                'created_at'
            ],
            Article::getSortableFields()
        );
    }
}
