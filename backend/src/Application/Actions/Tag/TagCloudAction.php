<?php

namespace App\Application\Actions\Tag;

use App\Application\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Psr\Log\LoggerInterface;

class TagCloudAction extends Action
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $cloud = $this->tagRepository->getTagCloud();
        return $this->respondWithData([
            'items' => $cloud,
        ]);
    }
}
