<?php

namespace Tests\Infrastructure\Persistence\Tag;

use App\Domain\Operations\DatabaseOperation;
use App\Domain\Pagination\SimpleNamedFilters;
use App\Domain\Pagination\SortDirection;
use App\Domain\Tag\Tag;
use App\Domain\Tag\TagNotFoundException;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use App\Infrastructure\Persistence\Tag\TagRepository;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Psr\Log\LoggerInterface;
use Tests\IntegrationTestCase;

class TagRepositoryTest extends IntegrationTestCase
{
    private TagRepositoryInterface $tagRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagRepository = new TagRepository(
            $this->container->get(DatabaseManagerInterface::class),
            $this->container->get(LoggerInterface::class)
        );
    }

    public function testFindById(): void
    {
        $tag = $this->tagRepository->findById(1);
        $this->assertEquals(1, $tag->id);
        $this->assertEquals('Chess', $tag->name);
        $this->assertEquals('chess', $tag->slug);
    }

    public function testFindByIdNotFound(): void
    {
        $tag = $this->tagRepository->findById(99999);
        $this->assertFalse($tag);
    }

    public function testFindBySlug(): void
    {
        $tag = $this->tagRepository->findBySlug('chess');
        $this->assertEquals(1, $tag->id);
        $this->assertEquals('Chess', $tag->name);
        $this->assertEquals('chess', $tag->slug);
    }

    public function testFindBySlugNotFound(): void
    {
        $tag = $this->tagRepository->findBySlug('nonexistent-slug');
        $this->assertFalse($tag);
    }

    public static function countProvider(): array
    {
        return [
            'filtering by name' => ['Chess', 1],
            'filtering by partial name' => ['good', 1],
            'without filtering' => ['', 2]
        ];
    }

    /** @dataProvider countProvider */
    public function testCount(?string $filter, int $expectedCount): void
    {
        $filters = new SimpleNamedFilters();
        $filters->name = $filter;
        $count = $this->tagRepository->count($filters);
        $this->assertEquals($expectedCount, $count);
    }

    public static function listProvider(): array
    {
        return [
            'default filters without name' => [
                [
                    'name' => '',
                    'sortBy' => 'name',
                    'sortOrder' => SortDirection::ASC,
                    'offset' => 0,
                    'limit' => 10
                ],
                2
            ],
            'limit filters without name' => [
                [
                    'name' => '',
                    'sortBy' => 'created_at',
                    'sortOrder' => SortDirection::DESC,
                    'offset' => 0,
                    'limit' => 1
                ],
                1
            ],
            'limit filters with name and sort' => [
                [
                    'name' => 'Chess',
                    'sortBy' => 'created_at',
                    'sortOrder' => SortDirection::DESC,
                    'offset' => 0,
                    'limit' => 10
                ],
                1
            ],
            'offset filters' => [
                [
                    'name' => '',
                    'sortBy' => 'created_at',
                    'sortOrder' => SortDirection::DESC,
                    'offset' => 10,
                    'limit' => 5
                ],
                0
            ]
        ];
    }

    /** @dataProvider listProvider */
    public function testList(array $filterParams, int $expectedCount): void
    {
        $filters = new SimpleNamedFilters();
        foreach ($filterParams as $key => $value) {
            $filters->{$key} = $value;
        }

        $rows = $this->tagRepository->list($filters);
        $this->assertEquals($expectedCount, count($rows));
    }

    public static function duplicatedProvider(): array
    {
        return [
            'with name and no id (lowercase)' => [
                'chess',
                null,
                true
            ],
            'with name and no id (mixed case)' => [
                'ChEsS',
                null,
                true
            ],
            'with name and same id (not duplicated)' => [
                'Chess',
                1,
                false
            ],
            'with name and different id (duplicated)' => [
                'Chess',
                2,
                true
            ],
            'with non-existent name' => [
                'nonexistent-tag',
                null,
                false
            ],
        ];
    }

    /** @dataProvider duplicatedProvider */
    public function testIsDuplicatedEntity(string $name, ?int $id, bool $duplicated): void
    {
        $result = $this->tagRepository->isDuplicatedEntity($name, $id);
        $this->assertEquals($duplicated, $result);
    }

    public function testUpdateTagNotFound(): void
    {
        $this->expectException(TagNotFoundException::class);

        $tag = new Tag();
        $tag->id = 99999;
        $this->tagRepository->save($tag);
    }

    public function testUpdateTagDuplicated(): void
    {
        $tag = new Tag();
        $tag->id = 1;
        $tag->name = 'good-tag'; // Trying to rename to existing tag name
        $op = $this->tagRepository->save($tag);
        $this->assertEquals(DatabaseOperation::ENTITY_DUPLICATED, $op->code);
    }

    public function testUpdateSuccess(): void
    {
        $tag = new Tag();
        $tag->id = 1;
        $tag->name = 'Updated Chess Tag';
        $op = $this->tagRepository->save($tag);
        $this->assertEquals(DatabaseOperation::ENTITY_UPDATED, $op->code);
        $this->assertEquals(1, $op->affectedRows);

        $updatedTag = $this->tagRepository->findById(1);
        $this->assertEquals('Updated Chess Tag', $updatedTag->name);
    }

    public function testInsertTagDuplicated(): void
    {
        $tag = new Tag();
        $tag->name = 'Chess'; // Existing tag name
        $op = $this->tagRepository->save($tag);
        $this->assertEquals(DatabaseOperation::ENTITY_DUPLICATED, $op->code);
    }

    public function testInsertSuccess(): void
    {
        $tag = new Tag();
        $tag->name = 'A New Tag';
        $op = $this->tagRepository->save($tag);
        $this->assertEquals(DatabaseOperation::ENTITY_CREATED, $op->code);
        $this->assertIsInt($op->entityId);
    }

    public function testDeleteById(): void
    {
        $op = $this->tagRepository->delete(1);
        $this->assertEquals(DatabaseOperation::ENTITY_DELETED, $op->code);
        $this->assertEquals(1, $op->affectedRows);
    }

    public function testGetTagCloud(): void
    {
        $rows = $this->tagRepository->getTagCloud();
        $this->assertIsArray($rows);
        $this->assertCount(2, $rows);

        $this->assertEqualsCanonicalizing(
            [
                ['name' => 'Chess', 'slug' => 'chess', 'tag_id' => 1, 'total_count' => 1],
                ['name' => 'good-tag', 'slug' => "good-tag'", 'tag_id' => 2, 'total_count' => 1],
            ],
            $rows
        );
    }

    public function testGetTagCloudWithLimit(): void
    {
        $rows = $this->tagRepository->getTagCloud(1);
        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);
    }
}
