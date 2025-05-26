<?php

namespace App\Application\Components;

use App\Domain\User\User;
use App\Infrastructure\Components\JWTServiceInterface;

class TokenGenerator
{
    public function __construct(
        private JWTServiceInterface $jwtService
    ) {
    }

    public function generateToken(User $user, bool $generateRefreshToken = true): array
    {
        $accessToken = $this->jwtService->issueToken($user);
        $refreshToken = $this->jwtService->issueToken($user, true);
        $expireCookieTime = time() + (int)$_ENV['JWT_REFRESH_TTL'];
        $domain = str_replace(['http://', 'https://'], '', $_ENV['JWT_ISSUER']);
        $secure = boolval($_ENV['PRODUCTION']) === true;

        if ($generateRefreshToken) {
            setcookie(
                $_ENV['COOKIE_RT_NAME'],
                $refreshToken,
                $expireCookieTime,
                '/',
                $domain,
                $secure,
                true
            );
        }

        return [
            'access_token' => $accessToken,
            'expires_in' => (int)$_ENV['JWT_TTL']
        ];
    }
}
