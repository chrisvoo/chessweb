<?php

use Scacchilatorre\Management\Commands\Migrate;
use Scacchilatorre\Management\Services\DbService;
use Scacchilatorre\Management\Services\FtpDump;
use Scacchilatorre\Management\Services\ImporterService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

$containerBuilder = new ContainerBuilder();
$containerBuilder->register('ftp_dump', FtpDump::class);

$containerBuilder->register('db_service', DbService::class)
    ->addArgument([
        DbService::HOST => $_ENV['LOCAL_DB_HOST'],
        DbService::DB_NAME => $_ENV['LOCAL_DB_NAME'],
        DbService::USER => $_ENV['LOCAL_DB_USER'],
        DbService::PASSWORD => $_ENV['LOCAL_DB_PASS'],
    ]);

$containerBuilder->register('import', ImporterService::class)
    ->addArgument(new Reference('db_service'));

$containerBuilder->register(Migrate::class, Migrate::class)
    ->addArgument(new Reference('ftp_dump'))
    ->addArgument(new Reference('import'))
    ->addTag('console.command');


return $containerBuilder;
