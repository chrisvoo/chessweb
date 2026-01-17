<?php

declare(strict_types=1);

namespace App\Application\Actions\Article;

use App\Application\Actions\Action;
use App\Application\Actions\Article\Validators\ExtraInfoValidator;
use App\Domain\Article\ArticleNotFoundException;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class ViewSingleArticleAction extends Action
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ExtraInfoValidator $validator,
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $queryParams = $this->request->getQueryParams();
        $this->validator->validate($this->request, $queryParams);

        $articleRef = $this->resolveArg('ref');
        if (is_numeric($articleRef)) {
            $articleId = (int) $articleRef;
            $article = $this->articleRepository->findByIdWithExtraDetails(
                $articleId,
                isset($queryParams['extra_info']) && in_array($queryParams['extra_info'], ['true', '1'])
            );
        } else {
            $article = $this->articleRepository->findBySlugWithExtraDetails(
                $articleRef,
                isset($queryParams['extra_info']) && in_array($queryParams['extra_info'], ['true', '1'])
            );
        }

        if (!$article) {
            throw new ArticleNotFoundException();
        }

        $this->logger->info("Article {$articleRef} was viewed.");

        return $this->respondWithData($article);
    }
}
