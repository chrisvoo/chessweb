<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\User\UserRepository;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use DI\ContainerBuilder;

use function DI\autowire;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        UserRepositoryInterface::class => autowire(UserRepository::class),
    ]);
};
