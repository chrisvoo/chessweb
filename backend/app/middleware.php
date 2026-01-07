<?php

declare(strict_types=1);

use Slim\App;

// global middlewares
return function (App $app) {
    $app->addBodyParsingMiddleware();
};
