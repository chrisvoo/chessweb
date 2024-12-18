<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\User;

use App\Domain\Operations\DatabaseOperation;
use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;

interface UserRepositoryInterface
{
    public function login(string $email, string $password): User|false;

    /**
     * @return User[]
     */
    public function findAll(): array;

    /**
     * @param int $id
     * @return User|false
     */
    public function findById(int $id): User|false;

    /**
     * Upsert of a user
     * @param User $user
     * @return DatabaseOperation
     */
    public function save(User $user): DatabaseOperation;

    /**
     * Delete a user
     * @param int $userId
     * @return DatabaseOperation
     */
    public function delete(int $userId): DatabaseOperation;
}
