<?php

namespace Tests\Domain\Mappers;

use App\Domain\Article\Article;
use App\Domain\Category\Category;
use App\Domain\Mappers\Mapper;
use App\Domain\Tag\Tag;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new Mapper();
    }

    public function testMapSimpleProperties(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Chess',
            'slug' => 'chess',
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ];

        $tag = $this->mapper->map($data, Tag::class);

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertEquals(1, $tag->id);
        $this->assertEquals('Chess', $tag->name);
        $this->assertEquals('chess', $tag->slug);
        $this->assertEquals('2021-01-01 00:00:00', $tag->created_at);
        $this->assertNull($tag->updated_at);
    }

    public function testMapIgnoresNonExistentProperties(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Chess',
            'slug' => 'chess',
            'non_existent_property' => 'some value'
        ];

        $tag = $this->mapper->map($data, Tag::class);

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertEquals(1, $tag->id);
        $this->assertFalse(property_exists($tag, 'non_existent_property'));
    }

    public function testMapThrowsExceptionForNonExistentClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistentClass does not exist.');

        $this->mapper->map(['id' => 1], 'NonExistentClass');
    }

    public function testMapWithNestedProperties(): void
    {
        $data = [
            'id' => 1,
            'author_id' => 1,
            'title' => 'Test Article',
            'content' => 'Content here',
            'slug' => 'test-article',
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null,
            'tags' => [
                [
                    'id' => 1,
                    'name' => 'Chess',
                    'slug' => 'chess',
                    'created_at' => '2021-01-01 00:00:00',
                    'updated_at' => null
                ],
                [
                    'id' => 2,
                    'name' => 'Tournament',
                    'slug' => 'tournament',
                    'created_at' => '2021-01-01 00:00:00',
                    'updated_at' => null
                ]
            ]
        ];

        $mapNestedProperties = [
            'tags' => ['class' => Tag::class]
        ];

        $article = $this->mapper->map($data, Article::class, $mapNestedProperties);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals(1, $article->id);
        $this->assertEquals('Test Article', $article->title);
        $this->assertCount(2, $article->tags);
        $this->assertInstanceOf(Tag::class, $article->tags[0]);
        $this->assertInstanceOf(Tag::class, $article->tags[1]);
        $this->assertEquals('Chess', $article->tags[0]->name);
        $this->assertEquals('Tournament', $article->tags[1]->name);
    }

    public function testMapWithMultipleNestedProperties(): void
    {
        $data = [
            'id' => 1,
            'author_id' => 1,
            'title' => 'Test Article',
            'content' => 'Content here',
            'slug' => 'test-article',
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null,
            'tags' => [
                [
                    'id' => 1,
                    'name' => 'Chess',
                    'slug' => 'chess',
                    'created_at' => '2021-01-01 00:00:00',
                    'updated_at' => null
                ]
            ],
            'categories' => [
                [
                    'id' => 1,
                    'name' => 'Tornei',
                    'slug' => 'tornei',
                    'created_at' => '2021-01-01 00:00:00',
                    'updated_at' => null
                ]
            ]
        ];

        $mapNestedProperties = [
            'tags' => ['class' => Tag::class],
            'categories' => ['class' => Category::class]
        ];

        $article = $this->mapper->map($data, Article::class, $mapNestedProperties);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertCount(1, $article->tags);
        $this->assertCount(1, $article->categories);
        $this->assertInstanceOf(Tag::class, $article->tags[0]);
        $this->assertInstanceOf(Category::class, $article->categories[0]);
    }

    public function testMapNestedPropertiesThrowsExceptionForNonExistentNestedClass(): void
    {
        $data = [
            'id' => 1,
            'author_id' => 1,
            'title' => 'Test Article',
            'content' => 'Content here',
            'slug' => 'test-article',
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null,
            'tags' => [
                ['id' => 1, 'name' => 'Chess']
            ]
        ];

        $mapNestedProperties = [
            'tags' => ['class' => 'NonExistentTagClass']
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class NonExistentTagClass does not exist.');

        $this->mapper->map($data, Article::class, $mapNestedProperties);
    }

    public function testMapEmptyNestedProperties(): void
    {
        $data = [
            'id' => 1,
            'author_id' => 1,
            'title' => 'Test Article',
            'content' => 'Content here',
            'slug' => 'test-article',
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null,
            'tags' => []
        ];

        $mapNestedProperties = [
            'tags' => ['class' => Tag::class]
        ];

        $article = $this->mapper->map($data, Article::class, $mapNestedProperties);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEmpty($article->tags);
    }

    public function testMapWithPartialData(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Chess'
            // Missing slug, created_at, updated_at
        ];

        $tag = $this->mapper->map($data, Tag::class);

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertEquals(1, $tag->id);
        $this->assertEquals('Chess', $tag->name);
    }
}
