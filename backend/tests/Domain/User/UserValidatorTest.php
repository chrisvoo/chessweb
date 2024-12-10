<?php

namespace Tests\Domain\User;

use App\Domain\User\UserValidator;
use App\Domain\Validators\ValidationScope;
use Generator;
use Tests\Domain\BaseValidator;

class UserValidatorTest extends BaseValidator
{
    private function validationDataProvider(): Generator
    {
        yield 'missing id' => [
            [
                'password' => 'passPoss!',
                'email' => 'user@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'is_admin' => true
            ],
            'id',
            ValidationScope::UPDATE
        ];

        yield 'missing password' => [
            [
                'email' => 'user@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'is_admin' => true
            ],
            'password',
            ValidationScope::CREATE
        ];

        yield 'password too short' => [
            [
                'email' => 'user@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'is_admin' => true,
                'password' => 'pass'
            ],
            'password',
            ValidationScope::CREATE
        ];

        yield 'missing email' => [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'is_admin' => true,
                'password' => 'passPoss!'
            ],
            'email',
            ValidationScope::CREATE
        ];

        yield 'invalid email' => [
            [
                'email' => 'user@example',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'is_admin' => true,
                'password' => 'passPoss!'
            ],
            'email',
            ValidationScope::CREATE
        ];

        yield 'missing first_name' => [
            [
                'email' => 'user@example',
                'first_name' => '',
                'last_name' => 'Doe',
                'is_admin' => true,
                'password' => 'passPoss!'
            ],
            'first_name',
            ValidationScope::CREATE
        ];

        yield 'first_name empty' => [
            [
                'email' => 'user@example',
                'last_name' => 'Doe',
                'is_admin' => true,
                'password' => 'passPoss!'
            ],
            'first_name',
            ValidationScope::CREATE
        ];

        yield 'missing last_name' => [
            [
                'email' => 'user@example',
                'first_name' => 'Marc',
                'is_admin' => true,
                'password' => 'passPoss!'
            ],
            'last_name',
            ValidationScope::CREATE
        ];

        yield 'last_name empty' => [
            [
                'email' => 'user@example',
                'first_name' => 'Marc',
                'last_name' => '',
                'is_admin' => true,
                'password' => 'passPoss!'
            ],
            'last_name',
            ValidationScope::CREATE
        ];

        yield 'invalid is_admin' => [
            [
                'email' => 'user@example',
                'first_name' => 'Marc',
                'last_name' => 'Buffalo',
                'is_admin' => 'gnagna',
                'password' => 'passPoss!',
                ''
            ],
            'is_admin',
            ValidationScope::CREATE
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
        parent::testValidate($payload, $invalidField, new UserValidator(), $scope);
    }
}
