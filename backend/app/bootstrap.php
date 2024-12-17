<?php

return function () {
    $dotenv = Dotenv\Dotenv::createImmutable(join('/', [__DIR__, '..']));
    $dotenv->load(); // throws an exception if the .env does not exist
    $dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'])->notEmpty();
    $dotenv->required('DB_PORT')->isInteger();
    $dotenv->required('JWT_TTL')->isInteger();
    $dotenv->required('JWT_REFRESH_TTL')->isInteger();
    $dotenv->required('PROJECT_ROOT')->notEmpty();
    $dotenv->required('JWT_ISSUER')->notEmpty();
    $dotenv->required('JWT_SECRET')->notEmpty();
    $dotenv->required('PRODUCTION')->isBoolean();
    $dotenv->ifPresent('DISPLAY_ERROR_DETAILS')->isBoolean();
    $dotenv->ifPresent('LOG_ERRORS')->isBoolean();
    $dotenv->ifPresent('LOGGER_LEVEL')->allowedValues([
        'DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'
    ]);
};
