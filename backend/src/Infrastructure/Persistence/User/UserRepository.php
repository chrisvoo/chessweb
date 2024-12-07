<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\Operations\DatabaseOperation;
use App\Domain\User\User;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use DateTime;
use Monolog\Logger;
use PDO;
use Psr\Log\LoggerInterface;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly DatabaseManagerInterface $databaseManager,
        protected LoggerInterface $logger
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
    public function findById(int $id): User|false
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

        return $result;
    }

    public function save(User $user): DatabaseOperation
    {
        if (isset($user->id)) {
            $userExist = $this->findById($user->id);

            if (!$userExist) {
                $dbOp = new DatabaseOperation();
                $dbOp->success = false;
                $dbOp->message = 'User not found';
                $dbOp->entityId = $user->id;
                return $dbOp;
            }

            $afftectedRows = $this->databaseManager->update(
                User::TABLE_NAME,
                [
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'is_admin' => $user->is_admin,
                    'valid' => $user->valid,
                    'updated_at' => (new DateTime())->format('Y-m-d H:i:s'),
                ],
                ['id' => $user->id]
            );

            $dbOp = new DatabaseOperation();
            $dbOp->success = true;
            $dbOp->message = 'User updated';
            $dbOp->entityId = $user->id;
            $dbOp->affectedRows = $afftectedRows;
            return $dbOp;
        } else {
            $lastInsertedId = $this->databaseManager->insert(
              User::TABLE_NAME,
              [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'password' => $user->password,
                'is_admin' => $user->is_admin,
                'valid' => $user->valid,
                'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
              ]
            );

            $dbOp = new DatabaseOperation();
            $dbOp->success = true;
            $dbOp->message = 'User created';
            $dbOp->entityId = (int)$lastInsertedId;
            $dbOp->affectedRows = 1;
            return $dbOp;
        }
    }

    public function delete(int $userId): DatabaseOperation
    {
        $this->databaseManager->deleteById(User::TABLE_NAME, $userId);
        $dbOp = new DatabaseOperation();
        $dbOp->success = true;
        $dbOp->message = 'User deleted';
        $dbOp->entityId = $userId;
        return $dbOp;
    }
}
