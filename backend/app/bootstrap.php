<?php

return function () {
    $dotenv = Dotenv\Dotenv::createImmutable(join('/', [__DIR__, '..']));
    $dotenv->load(); // throws an exception if the .env does not exist
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'])->notEmpty();
    $dotenv->required(['DB_PORT'])->isInteger();
    $dotenv->ifPresent(['LOG_ERRORS'])->isBoolean();
    $dotenv->ifPresent(['LOGGER_LEVEL'])->allowedValues(['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']);
};
