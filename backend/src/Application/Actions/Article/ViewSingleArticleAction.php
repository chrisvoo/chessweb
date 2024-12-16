<?php

declare(strict_types=1);

namespace App\Application\Actions\Article;

use App\Application\Actions\Action;
use App\Domain\Article\ArticleNotFoundException;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class ViewSingleArticleAction extends Action
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $articleId = (int) $this->resolveArg('id');
        $article = $this->articleRepository->findById($articleId);

        if (!$article) {
            throw new ArticleNotFoundException();
        }

        $this->logger->info("Article of id {$articleId} was viewed.");

        return $this->respondWithData($article);
    }
}
