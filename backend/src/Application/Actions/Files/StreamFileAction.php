<?php

namespace App\Application\Actions\Files;

use App\Application\Actions\Action;
use App\Domain\DomainException\InvalidRequestException;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

class StreamFileAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly Container $container
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $queryParams = $this->request->getQueryParams();
        if (empty($queryParams['file'])) {
            throw new InvalidRequestException(
                $this->request,
                'Missing file'
            );
        }

        $realBase = $this->container->get('upload_directory');
        $userpath = $realBase . DIRECTORY_SEPARATOR . $queryParams['file'];
        $realUserPath = realpath($userpath);
        $this->logger->info(
            json_encode([
                'real_base' => $realBase,
                'userpath' => $userpath,
                'realUserPath' => $realUserPath,
            ])
        );

        // avoid path traversal
        if ($realUserPath === false || !str_starts_with($realUserPath, $realBase)) {
            throw new InvalidRequestException(
                $this->request,
                'Invalid file path'
            );
        }

        $mime = mime_content_type($realUserPath);
        $stream = $this->streamFactory->createStreamFromFile($realUserPath);
        return $this->response
            ->withHeader('Content-Type', $mime)
            ->withBody($stream);
    }
}
