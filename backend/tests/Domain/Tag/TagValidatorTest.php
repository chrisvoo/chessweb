<?php

namespace Tests\Domain\Tag;

use App\Domain\Validators\SimpleNamedValidator;
use App\Domain\Validators\ValidationScope;
use Generator;
use Tests\Domain\BaseValidator;

class TagValidatorTest extends BaseValidator
{
    private function validationDataProvider(): Generator
    {
        yield 'missing id' => [
            [
                'name' => 'a_good_tag'
            ],
            'id',
            ValidationScope::UPDATE
        ];

        yield 'missing name' => [
            [

            ],
            'name',
            ValidationScope::UPDATE
        ];

        yield 'empty name' => [
            [
                'name' => ''
            ],
            'name',
            ValidationScope::UPDATE
        ];
    }

    /**
     * @dataProvider validationDataProvider
     * @param array $payload
     * @param string $invalidField
     * @param ValidationScope $scope
     * @return void
     */
    public function testValidateTag(
        array $payload,
        string $invalidField,
        ValidationScope $scope
    ): void
    {
        parent::testValidate($payload, $invalidField, new SimpleNamedValidator(), $scope);
    }
}
