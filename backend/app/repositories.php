<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Article\ArticleRepository;
use App\Infrastructure\Persistence\Article\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\Category\CategoryRepository;
use App\Infrastructure\Persistence\Category\CategoryRepositoryInterface;
use App\Infrastructure\Persistence\Tag\TagRepository;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use App\Infrastructure\Persistence\User\UserRepository;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use DI\ContainerBuilder;

use function DI\autowire;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        UserRepositoryInterface::class => autowire(UserRepository::class),
        TagRepositoryInterface::class => autowire(TagRepository::class),
        CategoryRepositoryInterface::class => autowire(CategoryRepository::class),
        ArticleRepositoryInterface::class => autowire(ArticleRepository::class),
    ]);
};
