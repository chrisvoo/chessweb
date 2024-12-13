<?php

namespace App\Application\Actions\Category;

use App\Application\Actions\Action;
use App\Domain\Category\CategoryNotFoundException;
use App\Domain\Tag\TagNotFoundException;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class DeleteCategoryAction extends Action
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
        $catId = $this->resolveArg('id');
        $op = $this->categoryRepository->delete($catId);

        if ($op->affectedRows === 0) {
            throw new CategoryNotFoundException();
        }

        $this->logger->info("Category deleted", ['id' => $op->entityId]);

        return $this->respondWithData($op);
    }
}
