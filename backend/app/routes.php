<?php

declare(strict_types=1);

use App\Application\Actions\Article\CreateArticleAction;
use App\Application\Actions\Article\DeleteArticleAction;
use App\Application\Actions\Article\ListArticlesAction;
use App\Application\Actions\Article\UpdateArticleAction;
use App\Application\Actions\Article\ViewSingleArticleAction;
use App\Application\Actions\Auth\LoginAction;
use App\Application\Actions\Auth\LogoutAction;
use App\Application\Actions\Auth\RefreshTokenAction;
use App\Application\Actions\Category\CategoryCloudAction;
use App\Application\Actions\Category\CreateCategoryAction;
use App\Application\Actions\Category\DeleteCategoryAction;
use App\Application\Actions\Category\ListCategoriesAction;
use App\Application\Actions\Category\UpdateCategoryAction;
use App\Application\Actions\Files\StreamFileAction;
use App\Application\Actions\Files\UploadAction;
use App\Application\Actions\Tag\CreateTagAction;
use App\Application\Actions\Tag\DeleteTagAction;
use App\Application\Actions\Tag\ListTagsAction;
use App\Application\Actions\Tag\TagCloudAction;
use App\Application\Actions\Tag\UpdateTagAction;
use App\Application\Actions\User\DeleteUserAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\CreateUserAction;
use App\Application\Actions\User\UpdateUserAction;
use App\Application\Actions\User\ViewSingleUserAction;
use App\Application\Middleware\AuthMiddleware;
use App\Infrastructure\Components\JWTServiceInterface;
//use Psr\Http\Message\ResponseInterface;
//use Psr\Http\Message\ServerRequestInterface;
//use Psr\Http\Server\RequestHandlerInterface;
//use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    /*
    $app->add(function (
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ) use ($app): ResponseInterface {
        if ($request->getMethod() === 'OPTIONS') {
            $response = $app->getResponseFactory()->createResponse();
        } else {
            $response = $handler->handle($request);
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache');

        if (ob_get_contents()) {
            ob_clean();
        }

        return $response;
    });*/

    $app->group('/api', function (Group $group) {
        $group->get('/user/{id}', ViewSingleUserAction::class);

        $group->get('/tags', ListTagsAction::class);
        $group->get('/tags/stats', TagCloudAction::class);

        $group->get('/categories', ListCategoriesAction::class);
        $group->get('/categories/stats', CategoryCloudAction::class);

        $group->get('/article/{id}', ViewSingleArticleAction::class);
        $group->get('/articles', ListArticlesAction::class);

        $group->get('/file', StreamFileAction::class);

        // auth
        $group->post('/login', LoginAction::class);
        $group->get('/logout', LogoutAction::class);
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

            // files
            $group->post('/upload', UploadAction::class);
        })->add(
            new AuthMiddleware(
                $group->getContainer()->get(JWTServiceInterface::class)
            )
        );
    });
};
