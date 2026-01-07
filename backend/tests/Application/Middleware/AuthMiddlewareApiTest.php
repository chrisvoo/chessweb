<?php

namespace Tests\Application\Middleware;

use App\Application\Middleware\AuthMiddleware;
use App\Infrastructure\Components\JWTServiceInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Uri;
use Tests\Helper\RequestHandlerHelper;
use Tests\ApiTestCase;

class AuthMiddlewareApiTest extends ApiTestCase
{
    public function testMissingHeaderThrowsException(): void
    {
        $uri = new Uri('http', 'localhost', 8080, '/api/article');
        $stream = (new StreamFactory())->createStreamFromFile('php://temp', 'w+');
        $request = new Request('POST', $uri, new Headers(), [], [], $stream);

        $middleware = new AuthMiddleware(
            $this->createMock(JWTServiceInterface::class)
        );
        $handler = new RequestHandlerHelper();

        $this->expectException(HttpForbiddenException::class);
        $middleware->process($request, $handler);
        $stream->close();
    }

    public function testMalformedHeaderThrowsException(): void
    {
        $uri = new Uri('http', 'localhost', 8080, '/api/article');
        $stream = (new StreamFactory())->createStreamFromFile('php://temp', 'w+');
        $headers = new Headers();
        $headers->setHeader('Content-Type', 'application/json');
        $headers->setHeader('Authorization', 'Bearer 123');
        $request = new Request('POST', $uri, $headers, [], [], $stream);

        $middleware = new AuthMiddleware(
            $this->createMock(JWTServiceInterface::class)
        );
        $handler = new RequestHandlerHelper();

        $this->expectException(HttpForbiddenException::class);
        $middleware->process($request, $handler);
        $stream->close();
    }

    public function testInvalidTokenThrowsException(): void
    {
        $uri = new Uri('http', 'localhost', 8080, '/api/article');
        $stream = (new StreamFactory())->createStreamFromFile('php://temp', 'w+');
        $headers = new Headers();
        $headers->setHeader('Content-Type', 'application/json');
        $headers->setHeader('Authorization', 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c');
        $request = new Request('POST', $uri, $headers, [], [], $stream);

        $jwtService = $this->getMockBuilder(JWTServiceInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['verifyToken', 'issueToken'])
            ->getMock();
        $jwtService->method('verifyToken')->willReturn(false);

        $middleware = new AuthMiddleware(
            $jwtService
        );
        $handler = new RequestHandlerHelper();

        $this->expectException(HttpForbiddenException::class);
        $middleware->process($request, $handler);
        $stream->close();
    }
}
