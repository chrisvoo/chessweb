<?php

declare(strict_types=1);

namespace App\Application\Actions\Health;

use App\Application\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class StatusAction extends Action
{
    public function __construct(
        protected LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $this->logger->info("StatusAction was viewed.");

        return $this->respondWithData([
            'status' => 'OK'
        ]);
    }
}
