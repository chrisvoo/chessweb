<?php

namespace App\Application\Actions\Tag;

use App\Application\Actions\Action;
use App\Domain\Mappers\Mapper;
use App\Domain\Tag\Tag;
use App\Domain\Validators\SimpleNamedValidator;
use App\Domain\Validators\ValidationScope;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class CreateTagAction extends Action
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        protected LoggerInterface $logger,
        private SimpleNamedValidator $validator
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $body = $this->request->getParsedBody() ?? [];
        $this->validator->validate($this->request, $body, ValidationScope::CREATE);

        $tag = (new Mapper())->map($body, Tag::class);
        $op = $this->tagRepository->save($tag);

        $this->logger->info("Tag created", ['id' => $op->entityId]);
        return $this->respondWithData($op);
    }
}
