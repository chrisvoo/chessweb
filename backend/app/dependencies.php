<?php

declare(strict_types=1);

use App\Infrastructure\Components\JWTService;
use App\Infrastructure\Components\JWTServiceInterface;
use App\Infrastructure\Persistence\DatabaseManager;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use DI\Container;
use DI\ContainerBuilder;
use Lcobucci\Clock\SystemClock;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\StreamFactory;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function () {
            $logger = new Logger($_ENV['LOGGER_NAME']);

            $logger->pushProcessor(new MemoryUsageProcessor());
            $logger->pushProcessor(new WebProcessor());

            $handler = new StreamHandler($_ENV['LOGGER_PATH'], $_ENV['LOGGER_LEVEL']);
            $handler->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s'));
            $logger->pushHandler($handler);

            return $logger;
        },
        DatabaseManagerInterface::class => function (Container $container) {
            return new DatabaseManager(
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
};
