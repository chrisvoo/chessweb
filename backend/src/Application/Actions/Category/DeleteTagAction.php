<?php

namespace App\Application\Actions\Tag;

use App\Application\Actions\Action;
use App\Domain\Tag\TagNotFoundException;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class DeleteTagAction extends Action
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $tagId = $this->resolveArg('id');
        $op = $this->tagRepository->delete($tagId);

        if ($op->affectedRows === 0) {
            throw new TagNotFoundException();
        }

        $this->logger->info("Tag deleted", ['id' => $op->entityId]);

        return $this->respondWithData($op);
    }
}
