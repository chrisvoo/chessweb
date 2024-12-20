<?php

declare(strict_types=1);

use App\Application\Middleware\JsonBodyParserMiddleware;
use Slim\App;

// global middlewares
return function (App $app) {
    $app->add(JsonBodyParserMiddleware::class);
};
