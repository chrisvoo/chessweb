<?php

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Domain\Mappers\Mapper;
use App\Domain\User\User;
use App\Domain\User\UserValidator;
use App\Domain\Validators\ValidationScope;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class CreateUserAction extends Action
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $body = $this->request->getParsedBody();
        $validator = new UserValidator();
        $validator->validate($this->request, $body, ValidationScope::CREATE);

        $user = (new Mapper())->map($body, User::class)->hashPassword();
        $op = $this->userRepository->save($user);

        $this->logger->info("User created", ['id' => $op->entityId]);
        return $this->respondWithData($op);
    }
}
