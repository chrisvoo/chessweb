<?php

namespace App\Application\Actions\Article\Validators;

use App\Domain\DomainException\InvalidRequestException;
use App\Domain\Validators\ValidationScope;
use App\Domain\Validators\ValidatorInterface;
use Respect\Validation\Exceptions\NestedValidationException;
use Slim\Psr7\Request;
use Respect\Validation\Validator as v;

class ExtraInfoValidator implements ValidatorInterface
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
        if (
            isset($data['extra_info']) &&
            !v::key(
                'extra_info',
                v::boolVal()->notEmpty(),
                false
            )->validate($data)
        ) {
            throw new InvalidRequestException(
                $request,
                'Invalid request',
                ['extra_info is not valid'],
            );
        }
    }
}
