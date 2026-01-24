<?php

declare(strict_types=1);

namespace Tests\Application\Handlers;

use App\Application\Actions\ActionError;
use App\Application\Handlers\HttpErrorHandler;
use App\Domain\DomainException\InvalidRequestException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class HttpErrorHandlerTest extends TestCase
{
    private function createHandler(?LoggerInterface $logger = null): HttpErrorHandler
    {
        $callableResolver = $this->createMock(CallableResolverInterface::class);
        $responseFactory = new ResponseFactory();
        $logger = $logger ?? $this->createMock(LoggerInterface::class);

        return new HttpErrorHandler($callableResolver, $responseFactory, $logger);
    }

    private function createRequest(): \Psr\Http\Message\ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest('GET', '/test');
    }

    private function invokeHandler(
        HttpErrorHandler $handler,
        \Throwable $exception,
        bool $displayErrorDetails = false
    ): \Psr\Http\Message\ResponseInterface {
        $request = $this->createRequest();
        return $handler($request, $exception, $displayErrorDetails, false, false);
    }

    private function getPayload(\Psr\Http\Message\ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true);
    }

    public function testHandleHttpNotFoundException(): void
    {
        $handler = $this->createHandler();
        $request = $this->createRequest();
        $exception = new HttpNotFoundException($request, 'Resource not found');

        $response = $this->invokeHandler($handler, $exception);
        $payload = $this->getPayload($response);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(404, $payload['statusCode']);
        $this->assertEquals(ActionError::RESOURCE_NOT_FOUND, $payload['error']['type']);
        $this->assertEquals('Resource not found', $payload['error']['description']);
    }

    public function testHandleHttpMethodNotAllowedException(): void
    {
        $handler = $this->createHandler();
        $request = $this->createRequest();
        $exception = new HttpMethodNotAllowedException($request, 'Method not allowed');

        $response = $this->invokeHandler($handler, $exception);
        $payload = $this->getPayload($response);

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals(ActionError::NOT_ALLOWED, $payload['error']['type']);
        $this->assertEquals('Method not allowed', $payload['error']['description']);
    }

    public function testHandleHttpUnauthorizedException(): void
    {
        $handler = $this->createHandler();
        $request = $this->createRequest();
        $exception = new HttpUnauthorizedException($request, 'Unauthorized');

        $response = $this->invokeHandler($handler, $exception);
        $payload = $this->getPayload($response);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(ActionError::UNAUTHENTICATED, $payload['error']['type']);
        $this->assertEquals('Unauthorized', $payload['error']['description']);
    }

    public function testHandleHttpForbiddenException(): void
    {
        $handler = $this->createHandler();
        $request = $this->createRequest();
        $exception = new HttpForbiddenException($request, 'Forbidden');

        $response = $this->invokeHandler($handler, $exception);
        $payload = $this->getPayload($response);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(ActionError::INSUFFICIENT_PRIVILEGES, $payload['error']['type']);
        $this->assertEquals('Forbidden', $payload['error']['description']);
    }

    public function testHandleHttpBadRequestException(): void
    {
        $handler = $this->createHandler();
        $request = $this->createRequest();
        $exception = new HttpBadRequestException($request, 'Bad request');

        $response = $this->invokeHandler($handler, $exception);
        $payload = $this->getPayload($response);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(ActionError::BAD_REQUEST, $payload['error']['type']);
        $this->assertEquals('Bad request', $payload['error']['description']);
    }

    public function testHandleHttpNotImplementedException(): void
    {
        $handler = $this->createHandler();
        $request = $this->createRequest();
        $exception = new HttpNotImplementedException($request, 'Not implemented');

        $response = $this->invokeHandler($handler, $exception);
        $payload = $this->getPayload($response);

        $this->assertEquals(501, $response->getStatusCode());
        $this->assertEquals(ActionError::NOT_IMPLEMENTED, $payload['error']['type']);
        $this->assertEquals('Not implemented', $payload['error']['description']);
    }

    public function testHandleGenericException(): void
    {
        $handler = $this->createHandler();
        $exception = new \Exception('Generic error');

        $response = $this->invokeHandler($handler, $exception);
        $payload = $this->getPayload($response);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(ActionError::SERVER_ERROR, $payload['error']['type']);
        $this->assertEquals('An internal error has occurred while processing your request.', $payload['error']['description']);
    }

    public function testHandleGenericExceptionWithDisplayErrorDetails(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Generic error');

        $handler = $this->createHandler($logger);
        $exception = new \Exception('Generic error');

        $response = $this->invokeHandler($handler, $exception, true);
        $payload = $this->getPayload($response);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals(ActionError::SERVER_ERROR, $payload['error']['type']);
    }

    public function testHandleExceptionWithExtraDetails(): void
    {
        $handler = $this->createHandler();
        $request = $this->createRequest();
        $extraDetails = ['field' => 'email', 'error' => 'invalid'];
        $exception = new InvalidRequestException($request, 'Validation failed', $extraDetails);

        $response = $this->invokeHandler($handler, $exception);
        $payload = $this->getPayload($response);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(ActionError::BAD_REQUEST, $payload['error']['type']);
        $this->assertEquals('Validation failed', $payload['error']['description']);
        $this->assertEquals($extraDetails, $payload['data']);
    }

    public function testResponseHasJsonContentType(): void
    {
        $handler = $this->createHandler();
        $request = $this->createRequest();
        $exception = new HttpNotFoundException($request, 'Not found');

        $response = $this->invokeHandler($handler, $exception);

        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }
}
