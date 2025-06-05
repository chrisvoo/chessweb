<?php

namespace App\Application\Actions\Article;

use App\Application\Actions\Action;
use App\Application\Actions\Article\Filters\ArticleFilters;
use App\Application\Actions\Article\Validators\ListArticlesFiltersValidator;
use App\Domain\Pagination\SimpleNamedFilters;
use App\Domain\Pagination\SortDirection;
use App\Domain\Validators\ListSimpleNamedQueryStringValidator;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class ListArticlesAction extends Action
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        protected LoggerInterface $logger,
        private ListArticlesFiltersValidator $validator
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

        $filters = new ArticleFilters();
        $filters->sortOrder = !empty($queryParams['sort_order'])
            ? SortDirection::fromValue($queryParams['sort_order'])
            : SortDirection::ASC;
        $filters->sortBy = $queryParams['sort_by'] ?? 'created_at';
        $filters->search_text = $queryParams['search_text'] ?? null;
        $filters->category_id = $queryParams['category_id'] ?? null;
        $filters->tag_id = $queryParams['tag_id'] ?? null;
        $filters->created_from = $queryParams['created_from'] ?? null;
        $filters->created_to = $queryParams['created_to'] ?? null;

        $page = $queryParams['page'] ?? 1;
        $page_size = $queryParams['page_size'] ?? 10;

        $filters->limit = $page_size + 1;
        $filters->offset = ($page * $page_size) - $page_size;

        $tags = $this->articleRepository->list($filters);
        $totalItems = $this->articleRepository->count($filters);

        $this->logger->debug('Got articles list', $tags);

        $response = [
            'items' => array_slice($tags, 0, $page_size),
            'total_items' => $totalItems,
            'total_pages' => ceil($totalItems / $page_size),
            'has_more_items' => count($tags) > $page_size,
            'page' => (int)$page,
            'page_size' => (int)$page_size
        ];

        $this->logger->info("Articles list was viewed.");

        return $this->respondWithData($response);
    }
}
