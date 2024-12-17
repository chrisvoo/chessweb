<?php

declare(strict_types=1);

use App\Infrastructure\Components\JWTService;
use App\Infrastructure\Components\JWTServiceInterface;
use App\Infrastructure\Persistence\DatabaseManager;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use DI\ContainerBuilder;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;

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
        DatabaseManagerInterface::class => function () {
            return new DatabaseManager(
                "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4;port={$_ENV['DB_PORT']}",
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        },
        JWTServiceInterface::class => function () {
            return new JWTService(
                new SystemClock(new DateTimeZone('UTC')),
            );
        }
    ]);
};
