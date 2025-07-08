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
            )
        ) {
            throw new HttpForbiddenException($request, 'Not authorized');
        }

        $user = $this->jwtService->verifyToken($matches[1]);
        if ($user === false) {
            throw new HttpForbiddenException($request, 'Not authorized');
        }

        // Add the user object (or just the ID) as a request attribute.
        // This creates a new request instance with the attribute.
        $requestWithUser = $request->withAttribute('user', $user);
        // Pass the NEW request object to the next handler in the chain.
        return $handler->handle($requestWithUser);
    }
}
