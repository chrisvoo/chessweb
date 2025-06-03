<?php

namespace App\Application\Actions\Tag;

use App\Application\Actions\Action;
use App\Domain\Pagination\SimpleNamedFilters;
use App\Domain\Pagination\SortDirection;
use App\Domain\Validators\ListSimpleNamedQueryStringValidator;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class ListTagsAction extends Action
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        protected LoggerInterface $logger,
        private ListSimpleNamedQueryStringValidator $validator
    ) {
        parent::__construct($logger);
    }
    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $queryParams = $this->request->getQueryParams();
        $this->validator->validate($this->request, $queryParams);

        $filters = new SimpleNamedFilters();
        $filters->sortOrder = !empty($queryParams['sort_order'])
            ? SortDirection::fromValue($queryParams['sort_order'])
            : SortDirection::ASC;
        $filters->sortBy = $queryParams['sort_by'] ?? 'name';
        $filters->name = $queryParams['name'] ?? '';
        $filters->all_items = $queryParams['all_items'] ?? false;

        if ($filters->all_items !== true) {
            $page = $queryParams['page'] ?? 1;
            $page_size = $queryParams['page_size'] ?? 10;

            $filters->limit = $page_size + 1;
            $filters->offset = ($page * $page_size) - $page_size;
        }

        $tags = $this->tagRepository->list($filters);
        $totalItems = $this->tagRepository->count($filters);

        $this->logger->debug('Got tags list', $tags);

        if ($filters->all_items !== true) {
            $response = [
                'items' => array_slice($tags, 0, $page_size),
                'total_items' => $totalItems,
                'total_pages' => ceil($totalItems / $page_size),
                'has_more_items' => count($tags) > $page_size,
                'page' => (int)$page,
                'page_size' => (int)$page_size
            ];
        } else {
            $response = [
                'items' => $tags,
            ];
        }

        $this->logger->info("Tags list was viewed.");

        return $this->respondWithData($response);
    }
}
