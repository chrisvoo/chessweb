<?php

namespace App\Application\Actions\Auth;

use App\Domain\DomainException\InvalidRequestException;
use App\Domain\Validators\ValidationScope;
use App\Domain\Validators\ValidatorInterface;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;
use Slim\Psr7\Request;

class LoginValidator implements ValidatorInterface
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
            v::allOf(
                v::key('email', v::email()->notEmpty()),
                v::key('password', v::stringType()->notEmpty())
            )->assert($data);
        } catch (NestedValidationException $e) {
            // since when using allOf the single keys are not available in the message details, we have to manually
            // add them
            $messages = $e->getMessages();
            $newMessages = [];
            foreach ($messages as $key => $message) {
                preg_match('/^\w+/', $message, $matches);
                if (!empty($matches)) {
                    $newMessages[$matches[0]] = $message;
                }
            }

            throw new InvalidRequestException(
                $request,
                'Invalid request',
                $newMessages
            );
        }
    }
}
