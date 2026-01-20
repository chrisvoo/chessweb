<?php

namespace Tests\Infrastructure\Persistence\Category;

use App\Domain\Category\Category;
use App\Domain\Category\CategoryNotFoundException;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\Pagination\SimpleNamedFilters;
use App\Domain\Pagination\SortDirection;
use App\Infrastructure\Persistence\Category\CategoryRepository;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use Psr\Log\LoggerInterface;
use Tests\IntegrationTestCase;

class CategoryRepositoryTest extends IntegrationTestCase
{
    private CategoryRepositoryInterface $categoryRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryRepository = new CategoryRepository(
            $this->container->get(DatabaseManagerInterface::class),
            $this->container->get(LoggerInterface::class)
        );
    }

    public function testFindById(): void
    {
        $category = $this->categoryRepository->findById(102);
        $this->assertEquals(102, $category->id);
        $this->assertEquals('Comunicazioni', $category->name);
        $this->assertEquals('comunicazioni', $category->slug);
    }

    public function testFindByIdNotFound(): void
    {
        $category = $this->categoryRepository->findById(10222);
        $this->assertFalse($category);
    }

    public function testFindBySlug(): void
    {
        $category = $this->categoryRepository->findBySlug('comunicazioni');
        $this->assertEquals(102, $category->id);
        $this->assertEquals('Comunicazioni', $category->name);
        $this->assertEquals('comunicazioni', $category->slug);
    }

    public function testFindBySlugNotFound(): void
    {
        $category = $this->categoryRepository->findBySlug('sadsa');
        $this->assertFalse($category);
    }

    public static function countProvider(): array
    {
        return [
            'filtering by name' => ['zioni', 1],
            'without filtering' => ['', 4]
        ];
    }

    /** @dataProvider countProvider */
    public function testCount(?string $filter, int $expectedCount): void
    {
        $filters = new SimpleNamedFilters();
        $filters->name = $filter;
        $count = $this->categoryRepository->count($filters);
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
                4
            ],
            'limit filters without name' => [
                [
                    'name' => '',
                    'sortBy' => 'created_at',
                    'sortOrder' => SortDirection::DESC,
                    'offset' => 0,
                    'limit' => 2
                ],
                2
            ],
            'limit filters with name and sort' => [
                [
                    'name' => 'zioni',
                    'sortBy' => 'created_at',
                    'sortOrder' => SortDirection::DESC,
                    'offset' => 0,
                    'limit' => 2
                ],
                1
            ],
            'offset filters' => [
                [
                    'name' => '',
                    'sortBy' => 'created_at',
                    'sortOrder' => SortDirection::DESC,
                    'offset' => 4,
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

        $rows = $this->categoryRepository->list($filters);
        $this->assertEquals($expectedCount, count($rows));
    }

    public static function duplicatedProvider(): array
    {
        return [
            'with name and no id (lowercase)' => [
               'comunicazioni',
                null,
                true
            ],
            'with name and no id (mixed)' => [
                'comunIcaZioni',
                null,
                true
            ],
            'with name and id' => [
                'comunIcaZioni',
                102,
                false
            ],
            'with name and wrong id' => [
                'comunIcaZioni',
                103,
                true
            ],
            'with name' => [
                'sddsdsd',
                null,
                false
            ],
        ];
    }

    /** @dataProvider duplicatedProvider */
    public function testIsDuplicatedEntity(string $name, ?int $id, bool $duplicated): void
    {
        $result = $this->categoryRepository->isDuplicatedEntity($name, $id);
        $this->assertEquals($duplicated, $result);
    }

    public function testUpdateCategoryNotFound(): void
    {
        $this->expectException(CategoryNotFoundException::class);

        $cat = new Category();
        $cat->id = 999;
        $this->categoryRepository->save($cat);
    }

    public function testUpdateCategoryDuplicated(): void
    {
        $cat = new Category();
        $cat->id = 102;
        $cat->name = 'Tornei';
        $op = $this->categoryRepository->save($cat);
        $this->assertEquals(DatabaseOperation::ENTITY_DUPLICATED, $op->code);
    }

    public function testUpdateSuccess(): void
    {
        $cat = new Category();
        $cat->id = 102;
        $cat->name = 'Zorro';
        $op = $this->categoryRepository->save($cat);
        $this->assertEquals(DatabaseOperation::ENTITY_UPDATED, $op->code);
        $this->assertEquals(1, $op->affectedRows);

        $upCategory = $this->categoryRepository->findById(102);
        $this->assertEquals('Zorro', $upCategory->name);
    }

    public function testInsertCategoryDuplicated(): void
    {
        $cat = new Category();
        $cat->name = 'Tornei';
        $op = $this->categoryRepository->save($cat);
        $this->assertEquals(DatabaseOperation::ENTITY_DUPLICATED, $op->code);
    }

    public function testInsertSuccess(): void
    {
        $cat = new Category();
        $cat->name = 'Una categoria';
        $op = $this->categoryRepository->save($cat);
        $this->assertEquals(DatabaseOperation::ENTITY_CREATED, $op->code);
        $this->assertIsInt($op->entityId);
    }

    public function testDeleteById(): void
    {
        $op = $this->categoryRepository->delete(102);
        $this->assertEquals(DatabaseOperation::ENTITY_DELETED, $op->code);
        $this->assertEquals(1, $op->affectedRows);
    }

    public function testGetCateogryCloud(): void
    {
        $rows = $this->categoryRepository->getCategoryCloud();
        $this->assertIsArray($rows);
        $this->assertCount(4, $rows);

        $this->assertEqualsCanonicalizing(
            [
                ['name' => 'Tornei', 'slug' => 'tornei', 'category_id' => 103, 'total_count' => 57],
                ['name' => 'Attività', 'slug' => 'attivita', 'category_id' => 104, 'total_count' => 32],
                ['name' => 'Comunicazioni', 'slug' => 'comunicazioni', 'category_id' => 102, 'total_count' => 19],
                ['name' => 'Tesseramento', 'slug' => 'tesseramento', 'category_id' => 105, 'total_count' => 4],
            ],
            $rows
        );
    }
}
