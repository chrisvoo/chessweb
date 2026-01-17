<?php

namespace App\Infrastructure\Persistence\Article;

use App\Application\Actions\Article\Filters\ArticleFilters;
use App\Domain\Article\Article;
use App\Domain\Article\ArticleNotFoundException;
use App\Domain\ArticlesCategories\ArticlesCategories;
use App\Domain\ArticlesTags\ArticlesTags;
use App\Domain\Category\Category;
use App\Domain\Common\Slugger;
use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\Tag\Tag;
use App\Infrastructure\Persistence\ArticlesCategories\ArticlesCategoriesRepositoryInterface;
use App\Infrastructure\Persistence\ArticlesTags\ArticlesTagsRepositoryInterface;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use DateTime;
use PDO;
use Psr\Log\LoggerInterface;

/* @TODO refactor the SQL composition */
class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManagerInterface $databaseManager,
        protected LoggerInterface $logger,
        private readonly ArticlesTagsRepositoryInterface $articlesTagsRepository,
        private readonly ArticlesCategoriesRepositoryInterface $articlesCategoriesRepository,
        private readonly TagRepositoryInterface $tagRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {
        $this->databaseManager->connect();
    }

    public function findById(int $id): Article|false
    {
        $table = Article::TABLE_NAME;
        /**
         * @var Article|false $result
         */
        $result = $this->databaseManager->row(
            <<<SQL
            SELECT id, title, author_id, content, slug, created_at, updated_at
            FROM $table
            WHERE id = :id
SQL,
            Article::class,
            ['id' => $id]
        );

        return $result;
    }

    public function findBySlug(string $slug): Article|false
    {
        $table = Article::TABLE_NAME;
        /**
         * @var Article|false $result
         */
        $result = $this->databaseManager->row(
            <<<SQL
            SELECT * FROM $table WHERE slug = :slug
SQL,
            Article::class,
            ['slug' => $slug]
        );

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function list(ArticleFilters $filters): array
    {
        $this->logger->debug('repo articles list filters', [json_encode($filters)]);

        $orderBy = isset($filters->sortBy) ? "{$filters->sortBy}" : 'created_at';
        $orderDirection = isset($filters->sortOrder) ? "{$filters->sortOrder->value}" : 'DESC';
        $offset = $filters->offset ?? 0;
        $limit = $filters->limit ?? 10;
        $params = [];
        $joins = [];
        $wheres = [];

        $able = Article::TABLE_NAME;
        $fields = [
            "a.id", "a.title", "a.slug", "author_id", "a.created_at", "a.updated_at"
        ];

        if (!$filters->skipContent) {
            $fields[] = "content";
        }

        if ($filters->extraInfo) {
            $fields[] = <<<SQL
                (
                    SELECT
                        COALESCE(
                            JSON_ARRAYAGG(
                                JSON_OBJECT('id', t.id, 'name', t.name, 'slug', t.slug)
                            ),
                            JSON_ARRAY()
                        )
                    FROM article_tags AS at
                    INNER JOIN tags AS t ON at.tag_id = t.id
                    WHERE at.article_id = a.id
                ) AS tags,
                (
                    SELECT
                        COALESCE(
                            JSON_ARRAYAGG(
                                JSON_OBJECT('id', c.id, 'name', c.name, 'slug', c.slug)
                            ),
                            JSON_ARRAY()
                        )
                    FROM article_categories AS ac
                    INNER JOIN categories AS c ON ac.category_id = c.id
                    WHERE ac.article_id = a.id
                ) AS categories
SQL;
        }

        $sql = "SELECT " . implode(", ", $fields) . " FROM $able a ";

        if (!empty($filters->tagSlug)) {
            $joins[] = "INNER JOIN article_tags at ON a.id = at.article_id ";
            $joins[] = "INNER JOIN tags t ON at.tag_id = t.id ";
            $wheres[] = 't.slug = :tag_slug';
            $params['tag_slug'] = $filters->tagSlug;
        }

        if (!empty($filters->categorySlug)) {
            $joins[] = "INNER JOIN article_categories ac ON a.id = ac.article_id ";
            $joins[] = "INNER JOIN categories c ON ac.category_id = c.id ";
            $wheres[] = 'c.slug = :cat_slug';
            $params['cat_slug'] = $filters->categorySlug;
        }

        /* PDO does not allow the same named placeholder to appear more than once in the SQL unless you're using
         * PDO::ATTR_EMULATE_PREPARES = true (which is off by default in many environments for security.
         * We also need to strip tags before searching for keywords in content, since the tags and blob data may match
         * the user's request */
        if (!empty($filters->searchText)) {
            $wheres[] = "(LOWER(a.title) LIKE :search1 OR " .
                        "REPLACE(REGEXP_REPLACE(LOWER(a.content), '<[^>]*>+', ''), '&nbsp;', ' ') LIKE :search2) ";
            $params['search1'] = "%{$filters->searchText}%";
            $params['search2'] = "%{$filters->searchText}%";
        }

        if (!empty($filters->createdFrom) && !empty($filters->createdTo)) {
            $wheres[] = "a.created_at BETWEEN :created_from AND :created_to ";
            $params['created_from'] = $filters->createdFrom;
            $params['created_to'] = $filters->createdTo;
        }

        if (!empty($joins)) {
            $sql .= implode(' ', $joins) . ' ';
        }

        if (!empty($wheres)) {
            $sql .= 'WHERE ' . implode(' AND ', $wheres) . ' ';
        }

        $sql .= "ORDER BY $orderBy $orderDirection LIMIT $limit OFFSET $offset";
        $this->logger->debug('list SQL', ['sql' => $sql, 'params' => $params]);

        if (!$filters->extraInfo) {
            return $this->databaseManager->rows(
                $sql,
                $params,
                PDO::FETCH_CLASS,
                Article::class
            );
        }

        $articles = $this->databaseManager->rows($sql, $params, PDO::FETCH_ASSOC);
        $items = [];

        foreach ($articles as $articleItem) {
            $article = new Article();
            $article->id = $articleItem['id'];
            $article->title = $articleItem['title'];
            $article->content = $articleItem['content'] ?? '';
            $article->slug = $articleItem['slug'];
            $article->author_id = $articleItem['author_id'];
            $article->created_at = $articleItem['created_at'];
            $article->updated_at = $articleItem['updated_at'];

            $tags = json_decode($articleItem['tags'], true);
            foreach ($tags as $tag) {
                $theTag = new Tag();
                $theTag->id = $tag['id'];
                $theTag->name = $tag['name'];
                $theTag->slug = $tag['slug'];
                $article->tags[] = $theTag;
            }

            $categories = json_decode($articleItem['categories'], true);
            foreach ($categories as $category) {
                $theCat = new Category();
                $theCat->id = $category['id'];
                $theCat->name = $category['name'];
                $theCat->slug = $category['slug'];
                $article->categories[] = $theCat;
            }

            $items[] = $article;
        }

        return $items;
    }

    public function count(ArticleFilters $filters): int
    {
        $able = Article::TABLE_NAME;
        $sql = "SELECT a.id FROM $able a ";
        $params = [];
        $joins = [];
        $wheres = [];

        if (!empty($filters->tagSlug)) {
            $joins[] = "INNER JOIN article_tags at ON a.id = at.article_id ";
            $joins[] = "INNER JOIN tags t ON at.tag_id = t.id ";
            $wheres[] = 't.slug = :cat_slug';
            $params['cat_slug'] = $filters->tagSlug;
        }

        if (!empty($filters->categorySlug)) {
            $joins[] = "INNER JOIN article_categories ac ON a.id = ac.article_id ";
            $joins[] = "INNER JOIN categories c ON ac.category_id = c.id ";
            $wheres[] = 'c.slug = :tag_slug';
            $params['tag_slug'] = $filters->categorySlug;
        }

        /* PDO does not allow the same named placeholder to appear more than once in the SQL unless you're using
         * PDO::ATTR_EMULATE_PREPARES = true (which is off by default in many environments for security */
        if (!empty($filters->searchText)) {
            $wheres[] = "(a.title LIKE :search1 OR a.content LIKE :search2) ";
            $params['search1'] = "%{$filters->searchText}%";
            $params['search2'] = "%{$filters->searchText}%";
        }

        if (!empty($filters->createdFrom) && !empty($filters->createdTo)) {
            $wheres[] = "a.created_at BETWEEN :created_from AND :created_to ";
            $params['created_from'] = $filters->createdFrom;
            $params['created_to'] = $filters->createdTo;
        }

        if (!empty($joins)) {
            $sql .= implode(' ', $joins) . ' ';
        }

        if (!empty($wheres)) {
            $sql .= 'WHERE ' . implode(' AND ', $wheres) . ' ';
        }

        $this->logger->debug('count SQL', ['sql' => $sql, 'params' => $params]);
        return $this->databaseManager->count(
            $sql,
            $params
        );
    }

    /**
     * @param Tag[] $tags
     * @throws DomainRecordNotFoundException
     */
    private function checkTags(array $tags): void
    {
        foreach ($tags as $tag) {
            if ($this->tagRepository->findById($tag->id) === false) {
                throw new DomainRecordNotFoundException(
                    'Tag not found',
                    DatabaseOperation::ENTITY_NOT_FOUND
                );
            }
        }
    }

    /**
     * @param Category[] $categories
     * @throws DomainRecordNotFoundException
     */
    private function checkCategories(array $categories): void
    {
        foreach ($categories as $category) {
            if ($this->categoryRepository->findById($category->id) === false) {
                throw new DomainRecordNotFoundException(
                    'Category not found',
                    DatabaseOperation::ENTITY_NOT_FOUND
                );
            }
        }
    }

    /**
     * @inheritDoc
     * @throws ArticleNotFoundException
     */
    public function save(Article $article): DatabaseOperation
    {
        $this->databaseManager->startTransaction();

        try {
            if (isset($article->id)) {
                $articleExist = $this->findById($article->id);

                if (!$articleExist) {
                    throw new ArticleNotFoundException();
                }

                $affectedRows = $this->databaseManager->update(
                    $article::TABLE_NAME,
                    [
                        'title' => $article->title,
                        'content' => $article->content,
                        'slug' => Slugger::generate($article->title) . '-' . $article->id,
                        'author_id' => $article->author_id,
                        'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
                    ],
                    ['id' => $article->id]
                );

                if (!empty($article->tags)) {
                    $this->checkTags($article->tags);
                    $rows = array_map(fn(Tag $tag) => [$article->id, $tag->id], $article->tags);

                    $this->articlesTagsRepository->deleteTagsForArticle($article->id);
                    $this->articlesTagsRepository->saveTagsForArticle($article->id, ['article_id', 'tag_id'], $rows);
                }

                if (!empty($article->categories)) {
                    $this->checkCategories($article->categories);
                    $rows = array_map(fn(Category $cat) => [$article->id, $cat->id], $article->categories);

                    $this->articlesCategoriesRepository->deleteCategoriesForArticle($article->id);
                    $this->articlesCategoriesRepository->saveCategoriesForArticle(
                        $article->id,
                        ['article_id', 'category_id'],
                        $rows
                    );
                }

                $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated($article->id);
                $dbOp->affectedRows = $affectedRows;

                $this->databaseManager->commit();

                return $dbOp;
            } else {
                $now = (new DateTime())->format('Y-m-d H:i:s');
                $lastInsertedId = $this->databaseManager->insert(
                    Article::TABLE_NAME,
                    [
                        'title' => $article->title,
                        'content' => $article->content,
                        'slug' => Slugger::generate($article->title . '-' . $now),
                        'author_id' => $article->author_id,
                        'created_at' => $now,
                    ]
                );
                $this->databaseManager->update(
                    $article::TABLE_NAME,
                    [
                        'slug' => Slugger::generate($article->title) . '-' . $lastInsertedId,
                    ],
                    ['id' => $lastInsertedId]
                );

                if (!empty($article->tags)) {
                    $this->checkTags($article->tags);
                    $rows = array_map(fn(Tag $tag) => [(int)$lastInsertedId, $tag->id], $article->tags);

                    $this->logger->debug('batch insert tag: ' . json_encode($rows));
                    $this->articlesTagsRepository->saveTagsForArticle(
                        (int)$lastInsertedId,
                        ['article_id', 'tag_id'],
                        $rows
                    );
                }

                if (!empty($article->categories)) {
                    $this->checkCategories($article->categories);
                    $rows = array_map(fn(Category $cat) => [$lastInsertedId, $cat->id], $article->categories);

                    $this->articlesCategoriesRepository->saveCategoriesForArticle(
                        $lastInsertedId,
                        ['article_id', 'category_id'],
                        $rows
                    );
                }

                $this->databaseManager->commit();

                return DatabaseOperation::newSingleEntitySuccessfullyCreated((int)$lastInsertedId);
            }
        } catch (DomainRecordNotFoundException $d) {
            throw $d;
        } catch (\Exception $e) {
            $this->databaseManager->rollback();
            $this->logger->error($e->getMessage());
            return DatabaseOperation::failed('', DatabaseOperation::SERVER_ERROR);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(int $articleId): DatabaseOperation
    {
        $affectedRows = $this->databaseManager->deleteById(Article::TABLE_NAME, $articleId);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted($articleId);
        $dbOp->affectedRows = $affectedRows;
        return $dbOp;
    }

    public function findByIdWithExtraDetails(int $id, bool $withExtraDetails = true): Article|false
    {
        if ($withExtraDetails === false) {
            return $this->findById($id);
        }

        $articlesTable = Article::TABLE_NAME;
        $articlesTagsTable = ArticlesTags::TABLE_NAME;
        $articlesCategoriesTable = ArticlesCategories::TABLE_NAME;

        $sql = <<<SQL
            SELECT
                a.id,
                a.title,
                a.content,
                a.slug,
                a.author_id,
                a.created_at,
                a.updated_at,
                (
                    SELECT
                        COALESCE(
                            JSON_ARRAYAGG(
                               JSON_OBJECT('id', t.id, 'name', t.name, 'slug', t.slug)
                            ),
                            JSON_ARRAY()
                        )
                    FROM $articlesTagsTable AS at
                    INNER JOIN tags AS t ON at.tag_id = t.id
                    WHERE at.article_id = a.id
                ) AS tags,
                (
                    SELECT
                        COALESCE(
                            JSON_ARRAYAGG(
                                JSON_OBJECT('id', c.id, 'name', c.name, 'slug', c.slug)
                            ),
                            JSON_ARRAY()
                        )
                    FROM $articlesCategoriesTable AS ac
                    INNER JOIN categories AS c ON ac.category_id = c.id
                    WHERE ac.article_id = a.id
                ) AS categories
            FROM $articlesTable AS a
            WHERE a.id = :id;
SQL;

        $data = $this->databaseManager->rows($sql, ['id' => $id], PDO::FETCH_ASSOC);

        if (!empty($data)) {
            $article = new Article();
            $article->id = $data[0]['id'];
            $article->title = $data[0]['title'];
            $article->content = $data[0]['content'];
            $article->slug = $data[0]['slug'];
            $article->author_id = $data[0]['author_id'];
            $article->created_at = $data[0]['created_at'];
            $article->updated_at = $data[0]['updated_at'];

            $tags = json_decode($data[0]['tags'], true);
            foreach ($tags as $tag) {
                $theTag = new Tag();
                $theTag->id = $tag['id'];
                $theTag->name = $tag['name'];
                $theTag->slug = $tag['slug'];
                $article->tags[] = $theTag;
            }

            $categories = json_decode($data[0]['categories'], true);
            foreach ($categories as $category) {
                $theCat = new Category();
                $theCat->id = $category['id'];
                $theCat->name = $category['name'];
                $theCat->slug = $category['slug'];
                $article->categories[] = $theCat;
            }

            return $article;
        }

        return false;
    }

    public function findBySlugWithExtraDetails(string $slug, bool $withExtraDetails = true): Article|false
    {
        if ($withExtraDetails === false) {
            return $this->findBySlug($slug);
        }

        $articlesTable = Article::TABLE_NAME;
        $articlesTagsTable = ArticlesTags::TABLE_NAME;
        $articlesCategoriesTable = ArticlesCategories::TABLE_NAME;

        $sql = <<<SQL
            SELECT
                a.id,
                a.title,
                a.content,
                a.slug,
                a.author_id,
                a.created_at,
                a.updated_at,
                (
                    SELECT
                        COALESCE(
                            JSON_ARRAYAGG(
                               JSON_OBJECT('id', t.id, 'name', t.name, 'slug', t.slug)
                            ),
                            JSON_ARRAY()
                        )
                    FROM $articlesTagsTable AS at
                    INNER JOIN tags AS t ON at.tag_id = t.id
                    WHERE at.article_id = a.id
                ) AS tags,
                (
                    SELECT
                        COALESCE(
                            JSON_ARRAYAGG(
                                JSON_OBJECT('id', c.id, 'name', c.name, 'slug', c.slug)
                            ),
                            JSON_ARRAY()
                        )
                    FROM $articlesCategoriesTable AS ac
                    INNER JOIN categories AS c ON ac.category_id = c.id
                    WHERE ac.article_id = a.id
                ) AS categories
            FROM $articlesTable AS a
            WHERE a.slug = :slug;
SQL;

        $data = $this->databaseManager->rows($sql, ['slug' => $slug], PDO::FETCH_ASSOC);

        if (!empty($data)) {
            $article = new Article();
            $article->id = $data[0]['id'];
            $article->title = $data[0]['title'];
            $article->content = $data[0]['content'];
            $article->slug = $data[0]['slug'];
            $article->author_id = $data[0]['author_id'];
            $article->created_at = $data[0]['created_at'];
            $article->updated_at = $data[0]['updated_at'];

            $tags = json_decode($data[0]['tags'], true);
            foreach ($tags as $tag) {
                $theTag = new Tag();
                $theTag->id = $tag['id'];
                $theTag->name = $tag['name'];
                $theTag->slug = $tag['slug'];
                $article->tags[] = $theTag;
            }

            $categories = json_decode($data[0]['categories'], true);
            foreach ($categories as $category) {
                $theCat = new Category();
                $theCat->id = $category['id'];
                $theCat->name = $category['name'];
                $theCat->slug = $category['slug'];
                $article->categories[] = $theCat;
            }

            return $article;
        }

        return false;
    }
}
