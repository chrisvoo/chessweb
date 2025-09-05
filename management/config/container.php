<?php

use Scacchilatorre\Management\Commands\Migrate;
use Scacchilatorre\Management\Services\FtpDump;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

$containerBuilder = new ContainerBuilder();
$containerBuilder->register('ftp_dump', FtpDump::class);

$containerBuilder->register(Migrate::class, Migrate::class)
    ->addArgument(new Reference('ftp_dump'))
    ->addTag('console.command');


return $containerBuilder;
