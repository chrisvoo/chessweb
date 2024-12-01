<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
//    $app->options('/{routes:.*}', function (Request $request, Response $response) {
//        // CORS Pre-Flight OPTIONS Request Handler
//        return $response;
//    });

    $app->group('/api', function (Group $group) {
        $group->get('', function (Request $request, Response $response) {
            $payload = json_encode(['hello' => 'world']);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        });
//        $group->get('/{id}', ViewUserAction::class);
    });
};
