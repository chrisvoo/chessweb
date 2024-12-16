<?php

namespace App\Application\Actions\Article;

use App\Application\Actions\Action;
use App\Domain\Article\ArticleNotFoundException;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class DeleteArticleAction extends Action
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $articleId = $this->resolveArg('id');
        $op = $this->articleRepository->delete($articleId);

        if ($op->affectedRows === 0) {
            throw new ArticleNotFoundException();
        }

        $this->logger->info("Article deleted", ['id' => $op->entityId]);

        return $this->respondWithData($op);
    }
}
