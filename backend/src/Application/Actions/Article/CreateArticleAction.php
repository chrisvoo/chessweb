<?php

namespace App\Application\Actions\Article;

use App\Application\Actions\Action;
use App\Domain\Article\Article;
use App\Domain\Article\ArticleValidator;
use App\Domain\Category\Category;
use App\Domain\Mappers\Mapper;
use App\Domain\Tag\Tag;
use App\Domain\Validators\ValidationScope;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class CreateArticleAction extends Action
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        protected LoggerInterface $logger,
        private readonly ArticleValidator $validator
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $user = $this->request->getAttribute('user');
        $body = $this->request->getParsedBody() ?? [];
        $this->validator->validate($this->request, $body, ValidationScope::CREATE);

        $article = (new Mapper())->map(
            $body,
            Article::class,
            [
                'tags' => ['class' => Tag::class, 'is_list' => true],
                'categories' => ['class' => Category::class, 'is_list' => true]
            ]
        );
        $article->author_id = $user->id;
        $op = $this->articleRepository->save($article);

        $this->logger->info("Article created", [
            'id' => $op->entityId,
            'by' => $user->id
        ]);
        return $this->respondWithData($op);
    }
}
