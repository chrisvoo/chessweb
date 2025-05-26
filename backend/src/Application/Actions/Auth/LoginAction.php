<?php

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Application\Components\TokenGenerator;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpForbiddenException;

class LoginAction extends Action
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        protected LoggerInterface $logger,
        private readonly LoginValidator $validator,
        private readonly TokenGenerator $tokenGenerator
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $body = $this->request->getParsedBody() ?? [];
        $this->validator->validate($this->request, $body);

        $user = $this->userRepository->login($body['email'], $body['password']);

        if (!$user) {
            throw new HttpForbiddenException($this->request);
        }

        $data = $this->tokenGenerator->generateToken($user);

        return $this->respondWithData([
            ...$data,
            'user' => $user
        ]);
    }
}
