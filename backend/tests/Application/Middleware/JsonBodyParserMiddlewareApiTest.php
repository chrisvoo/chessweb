<?php

namespace Tests\Application\Middleware;

use App\Application\Middleware\JsonBodyParserMiddleware;
use App\Domain\DomainException\InvalidRequestException;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Uri;
use Tests\Helper\RequestHandlerHelper;
use Tests\ApiTestCase;

class JsonBodyParserMiddlewareApiTest extends ApiTestCase
{
    public function testDoesNothingWithoutContentType(): void
    {
        $uri = new Uri('http', 'localhost', 8080, '/api/article');
        $headers = new Headers();
        $headers->setHeader('Content-Type', 'text/html');
        $stream = (new StreamFactory())->createStreamFromFile('php://temp', 'w+');
        $request = new Request('POST', $uri, $headers, [], [], $stream);

        $middleware = $this->createPartialMock(JsonBodyParserMiddleware::class, [
            'getContent'
        ]);
        $middleware->method('getContent')->willReturn('{"foo": "bar"}');
        $handler = new RequestHandlerHelper();
        $middleware->process($request, $handler);

        $this->assertNull($handler->getRequest()->getParsedBody());
        $stream->close();
    }

    public function testParseJsonWithCorrectContentType(): void
    {
        $uri = new Uri('http', 'localhost', 8080, '/api/article');
        $headers = new Headers();
        $headers->setHeader('Content-Type', 'application/json');
        $stream = (new StreamFactory())->createStreamFromFile('php://temp', 'w+');
        $request = new Request('POST', $uri, $headers, [], [], $stream);

        $middleware = $this->createPartialMock(JsonBodyParserMiddleware::class, [
            'getContent'
        ]);
        $middleware->method('getContent')->willReturn('{"foo": "bar"}');
        $handler = new RequestHandlerHelper();
        $middleware->process($request, $handler);

        $bodyModified = $handler->getRequest()->getParsedBody();
        $this->assertIsArray($bodyModified);
        $this->assertEquals('bar', $bodyModified['foo']);
        $stream->close();
    }

    public function testExceptionInvalidJson(): void
    {
        $uri = new Uri('http', 'localhost', 8080, '/api/article');
        $headers = new Headers();
        $headers->setHeader('Content-Type', 'application/json');
        $stream = (new StreamFactory())->createStreamFromFile('php://temp', 'w+');
        $request = new Request('POST', $uri, $headers, [], [], $stream);

        $middleware = $this->createPartialMock(JsonBodyParserMiddleware::class, [
            'getContent'
        ]);
        $middleware->method('getContent')->willReturn('{"foo: "bar"}');

        $this->expectException(InvalidRequestException::class);

        $handler = new RequestHandlerHelper();
        $middleware->process($request, $handler);
        $stream->close();
    }
}
