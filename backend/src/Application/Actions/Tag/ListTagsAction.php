<?php

namespace App\Application\Actions\Tag;

use App\Application\Actions\Action;
use App\Domain\Pagination\SimpleNamedFilters;
use App\Domain\Pagination\SortDirection;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;
use Psr\Log\LoggerInterface;

class ListTagsAction extends Action
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }
    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $queryParams = $this->request->getQueryParams();
        (new ListTagQueryStringValidator())->validate($this->request, $queryParams);

        $filters = new SimpleNamedFilters();
        $filters->sortOrder = $queryParams['sort_order'] ?? SortDirection::ASC;
        $filters->sortBy = $queryParams['sort_by'] ?? 'name';
        $filters->name = $queryParams['name'] ?? '';

        $page = $queryParams['page'] ?? 1;
        $page_size = $queryParams['page_size'] ?? 10;

        $filters->limit = $page_size;
        $filters->offset = ($page * $page_size) - $page_size + 1;

        $tags = $this->tagRepository->list($filters);
        $totalItems = $this->tagRepository->count($filters);

        $response = [
            'items' => array_slice($tags, 0, $filters->limit),
            'total_items' => $totalItems,
            'total_pages' => ceil($totalItems / $filters->limit),
            'has_more_items' => count($tags) > $filters->limit,
            'page' => (int)$page,
            'page_size' => (int)$page_size
        ];

        $this->logger->info("Tags list was viewed.");

        return $this->respondWithData($response);
    }
}
