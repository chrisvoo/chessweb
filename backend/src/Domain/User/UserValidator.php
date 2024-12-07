<?php

namespace App\Domain\User;

use App\Domain\DomainException\InvalidRequestException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;
use App\Domain\Validators\ValidatorInterface;
use App\Domain\Validators\ValidationScope;
use Slim\Psr7\Request;

class UserValidator implements ValidatorInterface
{
    public function validate(Request $request, array $data, ValidationScope $scope): void
    {
        try {
            if ($scope === ValidationScope::UPDATE) {
                v::numericVal()->setName('id')->assert($data['id'] ?? null);
            } else {
                v::key(
                    'password',
                    v::stringType()->notEmpty()->length(8)
                )->assert($data['password'] ?? null);
            }

            v::key(
                'email',
                v::email()
            )->key(
                'first_name',
                v::stringType()->notEmpty()
            )->key(
                'last_name',
                v::stringType()->notEmpty()
            )->key(
                'is_admin',
                v::boolVal(),
                false
            )->assert($data);
        } catch (NestedValidationException $e) {
            throw new InvalidRequestException($request, $e->getFullMessage());
        }
    }
}
