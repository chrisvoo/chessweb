<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use PDO;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private DatabaseManagerInterface $databaseManager
    ) {
        $this->databaseManager->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        return $this->databaseManager->rows(<<<SQL
            SELECT id, email, first_name, last_name, is_admin,
                   created_at, updated_at, valid
            FROM users
SQL,
        [],
        PDO::FETCH_CLASS,
        User::class
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): User
    {
        /**
         * @var User|false $result
         */
        $result = $this->databaseManager->row(<<<SQL
            SELECT id, email, first_name, last_name, is_admin,
                   created_at, updated_at, valid
            FROM users
            WHERE id = :id
SQL,
            User::class,
            ['id' => $id]
        );

        if (empty($result)) {
            throw new UserNotFoundException();
        }

        return $result;
    }
}
