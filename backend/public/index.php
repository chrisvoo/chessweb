<?php

declare(strict_types=1);

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Application\ResponseEmitter\ResponseEmitter;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;

require __DIR__ . '/../vendor/autoload.php';
$bootstrap = require __DIR__ . '/../app/bootstrap.php';
$bootstrap();

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Should be set to true in production
// $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Set up repositories
$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();
$callableResolver = $app->getCallableResolver();

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// Create Error Handler
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, (bool)$_ENV['DISPLAY_ERROR_DETAILS']);
register_shutdown_function($shutdownHandler);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Error Middleware
$app->addErrorMiddleware(
    (bool)$_ENV['DISPLAY_ERROR_DETAILS'],
    (bool)$_ENV['LOG_ERRORS'],
    (bool)$_ENV['LOG_ERROR_DETAILS']
)->setDefaultErrorHandler($errorHandler);

// Run App & Emit Response
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
