<?php

declare(strict_types=1);

use App\Application\Actions\User\DeleteUserAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\CreateUserAction;
use App\Application\Actions\User\UpdateUserAction;
use App\Application\Actions\User\ViewSingleUserAction;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
//    $app->options('/{routes:.*}', function (Request $request, Response $response) {
//        // CORS Pre-Flight OPTIONS Request Handler
//        return $response;
//    });

    $app->group('/api', function (Group $group) {
        // users
        $group->get('/user/{id}', ViewSingleUserAction::class);
        $group->get('/users', ListUsersAction::class);
        $group->post('/user', CreateUserAction::class);
        $group->put('/user/{id}', UpdateUserAction::class);
        $group->delete('/user/{id}', DeleteUserAction::class);
    });
};
