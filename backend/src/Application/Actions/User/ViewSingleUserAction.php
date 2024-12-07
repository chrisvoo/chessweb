<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Domain\User\UserNotFoundException;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class ViewSingleUserAction extends Action
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $userId = (int) $this->resolveArg('id');
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new UserNotFoundException();
        }

        $this->logger->info("User of id {$userId} was viewed.");

        return $this->respondWithData($user);
    }
}
