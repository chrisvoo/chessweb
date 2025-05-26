<?php

declare(strict_types=1);

use App\Application\Actions\Article\CreateArticleAction;
use App\Application\Actions\Article\DeleteArticleAction;
use App\Application\Actions\Article\ListArticlesAction;
use App\Application\Actions\Article\UpdateArticleAction;
use App\Application\Actions\Article\ViewSingleArticleAction;
use App\Application\Actions\Auth\LoginAction;
use App\Application\Actions\Auth\RefreshTokenAction;
use App\Application\Actions\Category\CreateCategoryAction;
use App\Application\Actions\Category\DeleteCategoryAction;
use App\Application\Actions\Category\ListCategoriesAction;
use App\Application\Actions\Category\UpdateCategoryAction;
use App\Application\Actions\Tag\CreateTagAction;
use App\Application\Actions\Tag\DeleteTagAction;
use App\Application\Actions\Tag\ListTagsAction;
use App\Application\Actions\Tag\UpdateTagAction;
use App\Application\Actions\User\DeleteUserAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\CreateUserAction;
use App\Application\Actions\User\UpdateUserAction;
use App\Application\Actions\User\ViewSingleUserAction;
use App\Application\Middleware\AuthMiddleware;
use App\Infrastructure\Components\JWTServiceInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
//    $app->options('/{routes:.*}', function (Request $request, Response $response) {
//        // CORS Pre-Flight OPTIONS Request Handler
//        return $response;
//    });

    $app->group('/api', function (Group $group) {
        $group->get('/user/{id}', ViewSingleUserAction::class);
        $group->get('/tags', ListTagsAction::class);
        $group->get('/categories', ListCategoriesAction::class);
        $group->get('/article/{id}', ViewSingleArticleAction::class);
        $group->get('/articles', ListArticlesAction::class);

        // auth
        $group->post('/login', LoginAction::class);
        $group->post('/refresh', RefreshTokenAction::class);

        $group->group('', function (Group $group) {
            // users
            $group->get('/users', ListUsersAction::class);
            $group->post('/user', CreateUserAction::class);
            $group->put('/user/{id}', UpdateUserAction::class);
            $group->delete('/user/{id}', DeleteUserAction::class);

            // tags
            $group->post('/tag', CreateTagAction::class);
            $group->put('/tag/{id}', UpdateTagAction::class);
            $group->delete('/tag/{id}', DeleteTagAction::class);

            // categories
            $group->post('/category', CreateCategoryAction::class);
            $group->put('/category/{id}', UpdateCategoryAction::class);
            $group->delete('/category/{id}', DeleteCategoryAction::class);

            // articles
            $group->post('/article', CreateArticleAction::class);
            $group->put('/article/{id}', UpdateArticleAction::class);
            $group->delete('/article/{id}', DeleteArticleAction::class);
        })->add(
            new AuthMiddleware(
                $group->getContainer()->get(LoggerInterface::class),
                $group->getContainer()->get(JWTServiceInterface::class)
            )
        );
    });
};
