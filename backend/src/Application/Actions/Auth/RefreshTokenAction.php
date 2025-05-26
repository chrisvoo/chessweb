<?php

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Application\Components\TokenGenerator;
use App\Infrastructure\Components\JWTServiceInterface;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpForbiddenException;

class RefreshTokenAction extends Action
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        protected LoggerInterface $logger,
        private readonly JWTServiceInterface $jwtService,
        private readonly TokenGenerator $tokenGenerator
    ) {
        parent::__construct($logger);
    }

    /**
     * @inheritDoc
     * @throws \DateMalformedStringException
     */
    protected function action(): Response
    {
        $refreshToken = $_COOKIE['rt'] ?? '';

        if (empty($refreshToken) || !$this->jwtService->verifyToken($refreshToken)) {
            throw new HttpForbiddenException($this->request);
        }

        $parser = new Parser(new JoseEncoder());
        $token = $parser->parse($refreshToken);
        assert($token instanceof UnencryptedToken);
        $userId = (int) $token->claims()->get('sub_id');

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new HttpForbiddenException($this->request);
        }

        $data = $this->tokenGenerator->generateToken($user);

        return $this->respondWithData($data);
    }
}
