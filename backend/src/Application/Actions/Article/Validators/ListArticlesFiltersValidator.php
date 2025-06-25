<?php

namespace App\Application\Actions\Article\Validators;

use App\Domain\Article\Article;
use App\Domain\DomainException\InvalidRequestException;
use App\Domain\Validators\PaginationValidatorObject;
use App\Domain\Validators\SortingValidatorObject;
use App\Domain\Validators\ValidationScope;
use App\Domain\Validators\ValidatorInterface;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;
use Slim\Psr7\Request;

class ListArticlesFiltersValidator implements ValidatorInterface
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
                (new PaginationValidatorObject())->getValidator(),
                (new SortingValidatorObject(Article::getSortableFields()))->getValidator(),
                v::key('search_text', v::stringType()->notEmpty(), false),
                v::key('title', v::stringType()->notEmpty(), false),
                v::key('tag_id', v::numericVal()->notEmpty(), false),
                v::key('category_id', v::numericVal()->notEmpty(), false),
                v::key('skip_content', v::boolVal()->notEmpty(), false),
                v::key('created_from', V::dateTime('Y-m-d H:i:s')->notEmpty(), false),
                v::key('created_to', V::dateTime('Y-m-d H:i:s')->notEmpty(), false)
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
