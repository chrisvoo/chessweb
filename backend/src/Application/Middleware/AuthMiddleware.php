<?php

namespace App\Application\Middleware;

use App\Infrastructure\Components\JWTServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JWTServiceInterface $jwtService
    ) {
    }
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeader('Authorization');

        if (
            empty($authHeader) ||
            !preg_match(
                '/Bearer\s((.*)\.(.*)\.(.*))/',
                trim($authHeader[0]),
                $matches
            ) ||
            $this->jwtService->verifyToken($matches[1]) === false
        ) {
            throw new HttpForbiddenException($request, 'Not authorized');
        }

        return $handler->handle($request);
    }
}
