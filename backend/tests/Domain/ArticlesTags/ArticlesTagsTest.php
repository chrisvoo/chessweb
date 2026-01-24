<?php

namespace Tests\Domain\ArticlesTags;

use App\Domain\ArticlesTags\ArticlesTags;
use PHPUnit\Framework\TestCase;

class ArticlesTagsTest extends TestCase
{
    public function testTableName(): void
    {
        $this->assertEquals('article_tags', ArticlesTags::TABLE_NAME);
    }

    public function testJsonSerialize(): void
    {
        $articlesTags = new ArticlesTags();
        $articlesTags->article_id = 123;
        $articlesTags->tag_id = 456;

        $expected = [
            'article_id' => 123,
            'tag_id' => 456
        ];

        $this->assertEquals($expected, $articlesTags->jsonSerialize());
    }

    public function testJsonEncode(): void
    {
        $articlesTags = new ArticlesTags();
        $articlesTags->article_id = 1;
        $articlesTags->tag_id = 2;

        $expectedJson = json_encode([
            'article_id' => 1,
            'tag_id' => 2
        ]);

        $this->assertEquals($expectedJson, json_encode($articlesTags));
    }
}
