<?php

namespace Tests\Domain\Category;

use App\Domain\Category\Category;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $id = 1;
        $name = "tournaments";
        $createdAt = '2021-01-01 00:00:00';
        $updatedAt = '2021-01-01 00:00:00';

        $tag = new Category();
        $tag->id = $id;
        $tag->name = $name;
        $tag->created_at = $createdAt;
        $tag->updated_at = $updatedAt;

        $expectedPayload = json_encode([
            'id' => $id,
            'name' => $name,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        $this->assertEquals($expectedPayload, json_encode($tag));
    }
}
