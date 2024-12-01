<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function () {
            $logger = new Logger($_ENV['LOGGER_NAME']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($_ENV['LOGGER_PATH'], $_ENV['LOGGER_LEVEL']);
            $logger->pushHandler($handler);

            return $logger;
        },
    ]);
};
