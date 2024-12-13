<?php

namespace App\Application\Actions\Category;

use App\Application\Actions\Action;
use App\Domain\Category\Category;
use App\Domain\Mappers\Mapper;
use App\Domain\Tag\Tag;
use App\Domain\Validators\SimpleNamedValidator;
use App\Domain\Validators\ValidationScope;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class UpdateCategoryAction extends Action
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $body = $this->request->getParsedBody();
        $body['id'] = $this->resolveArg('id');

        $validator = new SimpleNamedValidator();
        $validator->validate($this->request, $body, ValidationScope::UPDATE);

        $category = (new Mapper())->map($body, Category::class);
        $op = $this->categoryRepository->save($category);

        $this->logger->info("Category updated", ['id' => $op->entityId]);
        return $this->respondWithData($op);
    }
}
