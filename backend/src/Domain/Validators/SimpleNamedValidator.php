<?php

namespace App\Domain\Validators;

use App\Domain\DomainException\InvalidRequestException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;
use Slim\Psr7\Request;

class SimpleNamedValidator implements ValidatorInterface
{
    /**
     * Validates a request
     * @param Request $request The request
     * @param array $data The request data
     * @param ValidationScope|null $scope The validation scope
     */
    public function validate(Request $request, array $data, ?ValidationScope $scope = null): void
    {
        try {
            $chainedValidator = v::key('name', v::stringType()->notEmpty());

            if ($scope === ValidationScope::UPDATE) {
                $chainedValidator->key(
                    'id',
                    v::numericVal()->notOptional()
                );
            }

            $chainedValidator->key('name', v::stringType()->notEmpty()->notOptional())
                ->assert($data);
        } catch (NestedValidationException $e) {
            throw new InvalidRequestException(
                $request,
                'Invalid request',
                $e->getMessages()
            );
        }
    }
}
