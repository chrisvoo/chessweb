<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Handlers\HttpErrorHandler;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use DI\Container;
use DI\ContainerBuilder;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;

class TestCase extends PHPUnit_TestCase
{
    protected ?App $app;
    protected DatabaseManagerInterface|MockObject $databaseManager;

    public function setUp(): void
    {
        $this->app = $this->getAppInstance();
        /** @var Container $container */
        $container = $this->app->getContainer();
        $this->databaseManager = $this->mockDatabaseManager();
        $container->set(DatabaseManagerInterface::class, $this->databaseManager);
    }

    protected function mockDatabaseManager(): MockObject|DatabaseManagerInterface
    {
        $mock = $this->getMockBuilder(DatabaseManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->method('connect')->willReturn($mock);

        return $mock;
    }

    protected function mockRepository(string $repoClass): MockObject
    {
        $repositoryMock = $this
            ->getMockBuilder($repoClass)
            ->getMock();

        /** @var Container $container */
        $container = $this->app->getContainer();
        $container->set($repoClass, $repositoryMock);

        return $repositoryMock;
    }

    /**
     * @return App
     * @throws Exception
     */
    protected function getAppInstance(): App
    {
        // Instantiate PHP-DI ContainerBuilder
        $containerBuilder = new ContainerBuilder();

        // Container intentionally not compiled for tests.

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

        // Register middleware
        $middleware = require __DIR__ . '/../app/middleware.php';
        $middleware($app);

        $callableResolver = $app->getCallableResolver();
        $responseFactory = $app->getResponseFactory();
        $errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);
        $errorMiddleware = new ErrorMiddleware($callableResolver, $responseFactory, true, false, false);
        $errorMiddleware->setDefaultErrorHandler($errorHandler);
        $app->add($errorMiddleware);

        // Register routes
        $routes = require __DIR__ . '/../app/routes.php';
        $routes($app);

        return $app;
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $headers
     * @param array  $cookies
     * @param array  $serverParams
     * @return Request
     */
    protected function createRequest(
        string $method,
        string $path,
        string $queryString = '',
        array $headers = ['HTTP_ACCEPT' => 'application/json'],
        array $cookies = [],
        array $serverParams = []
    ): Request {
        $uri = new Uri('', '', 80, $path, $queryString);
        $handle = fopen('php://temp', 'w+');
        $stream = (new StreamFactory())->createStreamFromResource($handle);

        $h = new Headers();
        foreach ($headers as $name => $value) {
            $h->addHeader($name, $value);
        }

        return new SlimRequest($method, $uri, $h, $cookies, $serverParams, $stream);
    }
}
