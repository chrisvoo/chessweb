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
    public function validate(Request $request, array $data, ?ValidationScope $scope = null): void
    {
        try {
            $chainedValidator = v::key(
                'email',
                v::email()->notOptional()->notEmpty()
            )->key(
                'first_name',
                v::stringType()->notOptional()->notEmpty()
            )->key(
                'last_name',
                v::stringType()->notOptional()->notEmpty()
            )->key(
                'is_admin',
                v::boolVal(),
                false
            );

            if ($scope === ValidationScope::UPDATE) {
                $chainedValidator->key(
                    'id',
                    v::numericVal()
                );
            } else {
                $chainedValidator->key(
                    'password',
                    v::stringType()->notEmpty()->length(8)
                );
            }

            $chainedValidator->assert($data);
        } catch (NestedValidationException $e) {
            throw new InvalidRequestException(
                $request,
                'Invalid request',
                $e->getMessages()
            );
        }
    }
}
