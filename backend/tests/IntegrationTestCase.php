<?php

namespace Tests;

use App\Infrastructure\Components\JWTService;
use App\Infrastructure\Components\JWTServiceInterface;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use DateTimeZone;
use DI\Container;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Lcobucci\Clock\SystemClock;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\StreamFactory;
use Tests\Helper\DbTestManager;

class IntegrationTestCase extends TestCase
{
    protected Container $container;
    private DatabaseManagerInterface $databaseManager;

    protected function setUp(): void
    {
        parent::setUp();

        $containerBuilder = new ContainerBuilder();
        $dependencies = require __DIR__ . '/../app/dependencies.php';
        $dependencies($containerBuilder);

        $containerBuilder->addDefinitions([
            LoggerInterface::class => function () {
                $logger = new Logger($_ENV['LOGGER_NAME']);

                $handler = new StreamHandler($_ENV['LOGGER_PATH'], $_ENV['LOGGER_LEVEL']);
                $handler->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s'));
                $logger->pushHandler($handler);

                return $logger;
            },
            DatabaseManagerInterface::class => function (Container $container) {
                return new DbTestManager(
                    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4;port={$_ENV['DB_PORT']}",
                    $_ENV['DB_USER'],
                    $_ENV['DB_PASS'],
                    $container->get(LoggerInterface::class),
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            },
            JWTServiceInterface::class => function (Container $container) {
                return new JWTService(
                    $container->get(UserRepositoryInterface::class),
                    new SystemClock(new DateTimeZone('UTC')),
                );
            },
            StreamFactoryInterface::class => function (Container $container) {
                return new StreamFactory();
            }
        ]);

        // Set up repositories
        $repositories = require __DIR__ . '/../app/repositories.php';
        $repositories($containerBuilder);

        // Build PHP-DI Container instance
        $this->container = $containerBuilder->build();

        $this->databaseManager = $this->container->get(DatabaseManagerInterface::class);
        $this->databaseManager = $this->databaseManager->connect();
        $pdo = $this->databaseManager->getPdo();
        $pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $pdo = $this->databaseManager->getPdo();
        $pdo->rollBack();
        $pdo = null;
    }
}
