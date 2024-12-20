<?php

namespace Tests\Helper;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class RequestHandlerHelper implements RequestHandlerInterface
{
    private ServerRequestInterface $request;

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        return new Response();
    }
}
