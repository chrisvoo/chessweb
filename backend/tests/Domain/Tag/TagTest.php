<?php

namespace Tests\Domain\Tag;

use App\Domain\Tag\Tag;
use Tests\TestCase;

class TagTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $id = 1;
        $name = "a_good_tag";
        $createdAt = '2021-01-01 00:00:00';
        $updatedAt = '2021-01-01 00:00:00';

        $tag = new Tag();
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
