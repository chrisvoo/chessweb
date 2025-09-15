<?php

use Scacchilatorre\Management\Commands\Collect;
use Scacchilatorre\Management\Commands\Deploy;
use Scacchilatorre\Management\Commands\Migrate;
use Scacchilatorre\Management\Services\Crawler;
use Scacchilatorre\Management\Services\DbService;
use Scacchilatorre\Management\Services\ExtractorService;
use Scacchilatorre\Management\Services\FtpDump;
use Scacchilatorre\Management\Services\ImporterService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

$containerBuilder = new ContainerBuilder();
$containerBuilder->register(FtpDump::class, FtpDump::class);
$containerBuilder->register(ExtractorService::class, ExtractorService::class);
$containerBuilder->register(Crawler::class, Crawler::class);

$containerBuilder->register(DbService::class, DbService::class)
    ->addArgument([
        DbService::HOST => $_ENV['LOCAL_DB_HOST'],
        DbService::DB_NAME => $_ENV['LOCAL_DB_NAME'],
        DbService::USER => $_ENV['LOCAL_DB_USER'],
        DbService::PASSWORD => $_ENV['LOCAL_DB_PASS'],
    ]);

$containerBuilder->register(ImporterService::class, ImporterService::class)
    ->addArgument(new Reference(DbService::class))
    ->addArgument(new Reference(ExtractorService::class));

// commands
$containerBuilder->register(Migrate::class, Migrate::class)
    ->addArgument(new Reference(FtpDump::class))
    ->addArgument(new Reference(ImporterService::class))
    ->addTag('console.command');

$containerBuilder->register(Collect::class, Collect::class)
    ->addArgument(new Reference(Crawler::class));

$containerBuilder->register(Deploy::class, Deploy::class);

return $containerBuilder;
