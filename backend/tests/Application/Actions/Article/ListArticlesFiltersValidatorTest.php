<?php

namespace Tests\Application\Actions\Article;

use App\Application\Actions\Article\Validators\ListArticlesFiltersValidator;
use Generator;
use Tests\Domain\BaseValidator;

class ListArticlesFiltersValidatorTest extends BaseValidator
{
    private function validationDataProvider(): Generator
    {
        yield 'page_size negative num not valid' => [
            [
                'title' => 'test',
                'content' => 'test',
                'page_size' => -1
            ],
            'page_size'
        ];

        yield 'page_size null not valid' => [
            [
                'title' => 'test',
                'content' => 'test',
                'page_size' => null
            ],
            'page_size'
        ];

        yield 'page negative num not valid' => [
            [
                'title' => 'test',
                'content' => 'test',
                'page_size' => 10,
                'page' => -1
            ],
            'page'
        ];

        yield 'page null not valid' => [
            [
                'title' => 'test',
                'content' => 'test',
                'page_size' => 10,
                'page' => null
            ],
            'page'
        ];

        yield 'title null not valid' => [
            [
                'title' => null,
                'content' => 'test',
                'page_size' => 10,
                'page' => 1,
            ],
            'title'
        ];

        yield 'search_text null not valid' => [
            [
                'title' => 'a title',
                'search_text' => null,
                'content' => 'test',
                'page_size' => 10,
                'page' => 1,
            ],
            'search_text'
        ];

        yield 'tag_id null not valid' => [
            [
                'title' => 'hello',
                'content' => 'test',
                'tag_id' => null,
                'page_size' => 10,
                'page' => 1,
            ],
            'tag_id'
        ];

        yield 'category_id null not valid' => [
            [
                'title' => 'hello',
                'content' => 'test',
                'tag_id' => 5,
                'category_id' => null,
                'page_size' => 10,
                'page' => 1,
            ],
            'category_id'
        ];

        yield 'created_from null not valid' => [
            [
                'title' => 'hello',
                'content' => 'test',
                'created_from' => null,
                'tag_id' => 5,
                'category_id' => 8,
                'page_size' => 10,
                'page' => 1,
            ],
            'created_from'
        ];

        yield 'created_from format not valid' => [
            [
                'title' => 'hello',
                'content' => 'test',
                'created_from' => '2025-01-6',
                'tag_id' => 5,
                'category_id' => 8,
                'page_size' => 10,
                'page' => 1,
            ],
            'created_from'
        ];

        yield 'created_to null not valid' => [
            [
                'title' => 'hello',
                'content' => 'test',
                'created_from' => '2025-01-16 15:05:00',
                'created_to' => null,
                'tag_id' => 5,
                'category_id' => 8,
                'page_size' => 10,
                'page' => 1,
            ],
            'created_to'
        ];

        yield 'created_to format not valid' => [
            [
                'title' => 'hello',
                'content' => 'test',
                'created_from' => '2025-01-16 15:05:00',
                'created_to' => '2025-01-6',
                'search_text' => 'test',
                'tag_id' => 5,
                'category_id' => 8,
                'page_size' => 10,
                'page' => 1,
            ],
            'created_to'
        ];
    }

    /**
     * @dataProvider validationDataProvider
     * @param array $payload
     * @param string $invalidField
     * @return void
     */
    public function testValidateListQueryString(
        array $payload,
        string $invalidField
    ): void {
        parent::testValidate($payload, $invalidField, new ListArticlesFiltersValidator());
    }
}
