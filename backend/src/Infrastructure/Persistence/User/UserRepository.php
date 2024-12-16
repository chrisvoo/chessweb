<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\Operations\DatabaseOperation;
use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use DateTime;
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
        $table = User::TABLE_NAME;
        return $this->databaseManager->rows(<<<SQL
            SELECT id, email, first_name, last_name, is_admin,
                   created_at, updated_at, valid
            FROM $table
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
        $table = User::TABLE_NAME;
        /**
         * @var User|false $result
         */
        $result = $this->databaseManager->row(<<<SQL
            SELECT id, email, first_name, last_name, is_admin,
                   created_at, updated_at, valid
            FROM $table
            WHERE id = :id
SQL,
            User::class,
            ['id' => $id]
        );

        return $result;
    }

    /**
     * @throws UserNotFoundException
     */
    public function save(User $user): DatabaseOperation
    {
        if (isset($user->id)) {
            $userExist = $this->findById($user->id);

            if (!$userExist) {
                throw new UserNotFoundException();
            }

            $affectedRows = $this->databaseManager->update(
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

            $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated($user->id);
            $dbOp->affectedRows = $affectedRows;
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

            return DatabaseOperation::newSingleEntitySuccessfullyCreated((int)$lastInsertedId);
        }
    }

    public function delete(int $userId): DatabaseOperation
    {
        $affectedRows = $this->databaseManager->deleteById(User::TABLE_NAME, $userId);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted($userId);
        $dbOp->affectedRows = $affectedRows;
        return $dbOp;
    }

    public function login(string $email, string $password): User|false
    {
        $table = User::TABLE_NAME;
        $user = $this->databaseManager->row(
            <<<SQL
            SELECT *
            FROM $table
            WHERE email = :email
SQL,
            User::class,
            ['email' => $email]
        );

        if ($user && password_verify($password, $user->password)) {
            return $user;
        }

        return false;
    }
}
