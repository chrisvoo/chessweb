<?php

namespace App\Domain\Validators;

use App\Domain\DomainException\InvalidRequestException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;
use Slim\Psr7\Request;

class ListSimpleNamedValidator implements ValidatorInterface
{
    /**
     * Validates a request
     * @param Request $request The request
     * @param array $data The request data
     * @param ValidationScope|null $scope The validation scope
     * @throws NestedValidationException if the validation fails
     */
    public function validate(Request $request, array $data, ?ValidationScope $scope = null): void
    {
        try {
            $rule = v::key('name', v::stringType()->notEmpty()->notOptional());
            $rule->key(
                'id',
                v::numericVal()->notOptional()
            );

            v::each($rule)->assert($data);
        } catch (NestedValidationException $e) {
            throw new InvalidRequestException(
                $request,
                'Invalid request for ' . ($scope->name === ValidationScope::TAGS->name ? 'tag' : 'category'),
                $e->getMessages()
            );
        }
    }
}
