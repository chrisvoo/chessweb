<?php

namespace App\Application\Actions\Article;

use App\Application\Actions\Action;
use App\Domain\Article\Article;
use App\Domain\Article\ArticleValidator;
use App\Domain\Mappers\Mapper;
use App\Domain\Validators\ValidationScope;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class UpdateArticleAction extends Action
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        protected LoggerInterface $logger,
        private ArticleValidator $validator
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $body = $this->request->getParsedBody() ?? [];
        $body['id'] = $this->resolveArg('id');

        $this->validator->validate($this->request, $body, ValidationScope::UPDATE);

        $article = (new Mapper())->map($body, Article::class);
        $article->author_id = 1; // @TODO Fix when user is logged
        $op = $this->articleRepository->save($article);

        $this->logger->info("Article updated", ['id' => $op->entityId]);
        return $this->respondWithData($op);
    }
}
