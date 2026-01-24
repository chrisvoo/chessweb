<?php

namespace Tests\Domain\Article;

use App\Domain\Article\Article;
use App\Domain\Category\Category;
use App\Domain\Tag\Tag;
use PHPUnit\Framework\TestCase;

class ArticleTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $id = 1;
        $title = "A good article";
        $content = "<h1>Content</h1>";
        $slug = "a-good-article";
        $createdAt = '2021-01-01 00:00:00';
        $updatedAt = '2021-01-01 00:00:00';

        $article = new Article();
        $article->id = $id;
        $article->title = $title;
        $article->content = $content;
        $article->slug = $slug;
        $article->author_id = $id;
        $article->created_at = $createdAt;
        $article->updated_at = $updatedAt;

        $expectedPayload = json_encode([
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'slug' => $slug,
            'author_id' => $id,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        $this->assertEquals($expectedPayload, json_encode($article));
    }

    public function testJsonSerializeWithTags(): void
    {
        $article = new Article();
        $article->id = 1;
        $article->title = "Article with tags";
        $article->content = "Content";
        $article->slug = "article-with-tags";
        $article->author_id = 1;
        $article->created_at = '2021-01-01 00:00:00';
        $article->updated_at = null;

        $tag1 = new Tag();
        $tag1->id = 1;
        $tag1->name = 'Chess';
        $tag1->slug = 'chess';
        $tag1->created_at = '2021-01-01 00:00:00';
        $tag1->updated_at = null;

        $tag2 = new Tag();
        $tag2->id = 2;
        $tag2->name = 'Tournament';
        $tag2->slug = 'tournament';
        $tag2->created_at = '2021-01-01 00:00:00';
        $tag2->updated_at = null;

        $article->tags = [$tag1, $tag2];

        $serialized = $article->jsonSerialize();

        $this->assertArrayHasKey('tags', $serialized);
        $this->assertCount(2, $serialized['tags']);
        $this->assertSame($tag1, $serialized['tags'][0]);
        $this->assertSame($tag2, $serialized['tags'][1]);
    }

    public function testJsonSerializeWithCategories(): void
    {
        $article = new Article();
        $article->id = 1;
        $article->title = "Article with categories";
        $article->content = "Content";
        $article->slug = "article-with-categories";
        $article->author_id = 1;
        $article->created_at = '2021-01-01 00:00:00';
        $article->updated_at = null;

        $category1 = new Category();
        $category1->id = 1;
        $category1->name = 'Tornei';
        $category1->slug = 'tornei';
        $category1->created_at = '2021-01-01 00:00:00';
        $category1->updated_at = null;

        $category2 = new Category();
        $category2->id = 2;
        $category2->name = 'Comunicazioni';
        $category2->slug = 'comunicazioni';
        $category2->created_at = '2021-01-01 00:00:00';
        $category2->updated_at = null;

        $article->categories = [$category1, $category2];

        $serialized = $article->jsonSerialize();

        $this->assertArrayHasKey('categories', $serialized);
        $this->assertCount(2, $serialized['categories']);
        $this->assertSame($category1, $serialized['categories'][0]);
        $this->assertSame($category2, $serialized['categories'][1]);
    }

    public function testJsonSerializeWithTagsAndCategories(): void
    {
        $article = new Article();
        $article->id = 1;
        $article->title = "Full article";
        $article->content = "Content";
        $article->slug = "full-article";
        $article->author_id = 1;
        $article->created_at = '2021-01-01 00:00:00';
        $article->updated_at = null;

        $tag = new Tag();
        $tag->id = 1;
        $tag->name = 'Chess';
        $tag->slug = 'chess';
        $tag->created_at = '2021-01-01 00:00:00';
        $tag->updated_at = null;

        $category = new Category();
        $category->id = 1;
        $category->name = 'Tornei';
        $category->slug = 'tornei';
        $category->created_at = '2021-01-01 00:00:00';
        $category->updated_at = null;

        $article->tags = [$tag];
        $article->categories = [$category];

        $serialized = $article->jsonSerialize();

        $this->assertArrayHasKey('tags', $serialized);
        $this->assertArrayHasKey('categories', $serialized);
        $this->assertCount(1, $serialized['tags']);
        $this->assertCount(1, $serialized['categories']);
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
