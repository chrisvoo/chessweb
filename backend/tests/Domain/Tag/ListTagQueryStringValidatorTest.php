<?php

namespace Tests\Domain\Tag;

use App\Application\Actions\Tag\ListTagQueryStringValidator;
use Generator;
use Tests\Domain\BaseValidator;

class ListTagQueryStringValidatorTest extends BaseValidator
{
    private function validationDataProvider(): Generator
    {
        yield 'page_size negative num not valid' => [
            [
                'name' => 'test',
                'page_size' => -1
            ],
            'page_size'
        ];

        yield 'page_size null not valid' => [
            [
                'name' => 'test',
                'page_size' => null
            ],
            'page_size'
        ];

        yield 'page negative num not valid' => [
            [
                'name' => 'test',
                'page_size' => 10,
                'page' => -1
            ],
            'page'
        ];

        yield 'page null not valid' => [
            [
                'name' => 'test',
                'page_size' => 10,
                'page' => null
            ],
            'page'
        ];

        yield 'name null not valid' => [
            [
                'name' => null,
                'page_size' => 10,
                'page' => 1,
            ],
            'name'
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
        parent::testValidate($payload, $invalidField, new ListTagQueryStringValidator());
    }
}
