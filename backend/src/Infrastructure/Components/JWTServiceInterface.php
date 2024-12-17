<?php

namespace App\Infrastructure\Components;

use App\Domain\User\User;

interface JWTServiceInterface
{
    /**
     * Issue a JWT token
     * @return string JWT token
     */
    public function issueToken(User $user, bool $isRefresh = false): string;

    /**
     * It verifies a JWT token
     * @param string $token JWT token
     * @return User|bool The user that the token belongs to or false
     */
    public function verifyToken(string $token): User|bool;
}
