<?php

namespace App\Domain\Article;

use App\Domain\DomainException\InvalidRequestException;
use Respect\Validation\Validator as v;
use App\Domain\Validators\ValidationScope;
use App\Domain\Validators\ValidatorInterface;
use Respect\Validation\Exceptions\NestedValidationException;
use Slim\Psr7\Request;

class ArticleValidator implements ValidatorInterface
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
            $chainedValidator = v::key(
                'title',
                v::stringType()->notOptional()->notEmpty()
            )->key(
                'content',
                v::stringType()->notOptional()->notEmpty()
            );

            if ($scope === ValidationScope::UPDATE) {
                $chainedValidator->key(
                    'id',
                    v::numericVal()
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
