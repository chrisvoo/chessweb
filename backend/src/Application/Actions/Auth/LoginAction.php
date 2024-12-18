<?php

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Infrastructure\Components\JWTServiceInterface;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpForbiddenException;

class LoginAction extends Action
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        protected LoggerInterface $logger,
        private LoginValidator $validator,
        private JWTServiceInterface $jwtService
    ) {
        parent::__construct($logger);
    }

    /**
     * @inheritDoc
     * @throws \DateMalformedStringException
     */
    protected function action(): Response
    {
        $body = $this->request->getParsedBody() ?? [];
        $this->validator->validate($this->request, $body);

        $user = $this->userRepository->login($body['email'], $body['password']);

        if (!$user) {
            throw new HttpForbiddenException($this->request);
        }

        $accessToken = $this->jwtService->issueToken($user);
        $refreshToken = $this->jwtService->issueToken($user, true);
        $expireCookieTime = time() + (int)$_ENV['JWT_REFRESH_TTL'];
        $domain = str_replace(['http://', 'https://'], '', $_ENV['JWT_ISSUER']);
        $secure = boolval($_ENV['PRODUCTION']) === true;

        setcookie(
            $_ENV['COOKIE_RT_NAME'],
            $refreshToken,
            $expireCookieTime,
            '/',
            $domain,
            $secure,
            true
        );

        return $this->respondWithData([
            'access_token' => $accessToken,
            'expires_in' => (int)$_ENV['JWT_TTL'],
            'user' => $user
        ]);
    }
}
