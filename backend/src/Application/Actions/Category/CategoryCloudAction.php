<?php

namespace App\Application\Actions\Category;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class CategoryCloudAction extends Action
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $cloud = $this->categoryRepository->getCategoryCloud();
        return $this->respondWithData([
            'items' => $cloud,
        ]);
    }
}
