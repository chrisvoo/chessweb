<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Auth;

use App\Application\Actions\Auth\LogoutAction;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class LogoutActionApiTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Set required environment variables
        $_ENV['JWT_ISSUER'] = 'https://example.com';
        $_ENV['PRODUCTION'] = 'false';
        $_ENV['COOKIE_RT_NAME'] = 'rt';
    }

    private function invokeAction(LogoutAction $action): \Psr\Http\Message\ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/logout');
        $response = (new ResponseFactory())->createResponse();

        $reflection = new \ReflectionClass($action);

        $requestProp = $reflection->getProperty('request');
        $requestProp->setAccessible(true);
        $requestProp->setValue($action, $request);

        $responseProp = $reflection->getProperty('response');
        $responseProp->setAccessible(true);
        $responseProp->setValue($action, $response);

        $actionMethod = $reflection->getMethod('action');
        $actionMethod->setAccessible(true);

        // Use output buffering to suppress setcookie header warning in tests
        ob_start();
        $result = @$actionMethod->invoke($action);
        ob_end_clean();

        return $result;
    }

    public function testLogoutSuccess(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $action = new LogoutAction($logger);

        $response = $this->invokeAction($action);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('message', $body['data']);
        $this->assertEquals('Logged out', $body['data']['message']);
    }

    public function testLogoutReturnsCorrectPayload(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $action = new LogoutAction($logger);

        $response = $this->invokeAction($action);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals(200, $body['statusCode']);
        $this->assertEquals(['message' => 'Logged out'], $body['data']);
    }
}
