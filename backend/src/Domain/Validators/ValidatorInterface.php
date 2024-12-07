<?php

namespace App\Domain\Validators;


use Respect\Validation\Exceptions\NestedValidationException;
use Slim\Psr7\Request;

interface ValidatorInterface
{
    /**
     * Validates a request
     * @param Request $request The request
     * @param array $data The request data
     * @param ValidationScope $scope The validation scope
     * @throws NestedValidationException if the validation fails
     */
    public function validate(Request $request, array $data, ValidationScope $scope): void;
}
