<?php

namespace Tests\Domain\ArticlesCategories;

use App\Domain\ArticlesCategories\ArticlesCategories;
use PHPUnit\Framework\TestCase;

class ArticlesCategoriesTest extends TestCase
{
    public function testTableName(): void
    {
        $this->assertEquals('article_categories', ArticlesCategories::TABLE_NAME);
    }

    public function testJsonSerialize(): void
    {
        $articlesCategories = new ArticlesCategories();
        $articlesCategories->article_id = 123;
        $articlesCategories->category_id = 456;

        $expected = [
            'article_id' => 123,
            'tag_id' => 456  // Note: The original code has 'tag_id' instead of 'category_id'
        ];

        $this->assertEquals($expected, $articlesCategories->jsonSerialize());
    }

    public function testJsonEncode(): void
    {
        $articlesCategories = new ArticlesCategories();
        $articlesCategories->article_id = 1;
        $articlesCategories->category_id = 2;

        $expectedJson = json_encode([
            'article_id' => 1,
            'tag_id' => 2  // Note: The original code has 'tag_id' instead of 'category_id'
        ]);

        $this->assertEquals($expectedJson, json_encode($articlesCategories));
    }
}
