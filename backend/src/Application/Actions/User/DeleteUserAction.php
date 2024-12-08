<?php

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Domain\Mappers\Mapper;
use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Domain\User\UserValidator;
use App\Domain\Validators\ValidationScope;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class DeleteUserAction extends Action
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
        $userId = $this->resolveArg('id');
        $op = $this->userRepository->delete($userId);

        if ($op->affectedRows === 0) {
            throw new UserNotFoundException();
        }

        $this->logger->info("User deleted", ['id' => $op->entityId]);

        return $this->respondWithData($op);
    }
}
