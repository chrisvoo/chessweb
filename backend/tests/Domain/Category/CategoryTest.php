<?php

namespace Tests\Domain\Category;

use App\Domain\Category\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testTableName(): void
    {
        $this->assertEquals('categories', Category::TABLE_NAME);
    }

    public function testJsonSerialize(): void
    {
        $id = 1;
        $name = "tournaments";
        $slug = "tournaments";
        $createdAt = '2021-01-01 00:00:00';
        $updatedAt = '2021-01-01 00:00:00';

        $category = new Category();
        $category->id = $id;
        $category->name = $name;
        $category->slug = $slug;
        $category->created_at = $createdAt;
        $category->updated_at = $updatedAt;

        $expectedPayload = json_encode([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        $this->assertEquals($expectedPayload, json_encode($category));
    }

    public function testGetSortableFields(): void
    {
        $sortableFields = Category::getSortableFields();

        $this->assertIsArray($sortableFields);
        $this->assertContains('id', $sortableFields);
        $this->assertContains('name', $sortableFields);
        $this->assertContains('slug', $sortableFields);
        $this->assertContains('created_at', $sortableFields);
        $this->assertContains('updated_at', $sortableFields);
    }
}
