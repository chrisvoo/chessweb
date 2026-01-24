<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Auth;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Application\Components\TokenGenerator;
use App\Domain\User\User;
use App\Infrastructure\Components\JWTServiceInterface;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use DI\Container;
use Tests\ApiTestCase;
use Tests\Helper\Faker;

class LoginActionApiTest extends ApiTestCase
{
    public function testLoginSuccess(): void
    {
        $user = Faker::fakeData(User::class);
        $user->id = 1;
        $user->email = 'test@example.com';

        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $repo->method('login')->willReturn($user);

        // Mock TokenGenerator
        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenGenerator->method('generateToken')->willReturn([
            'access_token' => 'test_token',
            'expires_in' => 3600
        ]);

        /** @var Container $container */
        $container = $this->app->getContainer();
        $container->set(TokenGenerator::class, $tokenGenerator);

        $request = $this->createRequest('POST', '/api/login');
        $request = $request->withParsedBody([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('access_token', $payload['data']);
        $this->assertArrayHasKey('user', $payload['data']);
    }

    public function testLoginForbiddenWhenUserNotFound(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $repo->method('login')->willReturn(false);

        $request = $this->createRequest('POST', '/api/login');
        $request = $request->withParsedBody([
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]);

        $response = $this->app->handle($request);

        $this->assertEquals(403, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('error', $payload);
        $this->assertEquals(ActionError::INSUFFICIENT_PRIVILEGES, $payload['error']['type']);
    }

    public function testLoginBadRequestWithInvalidEmail(): void
    {
        $request = $this->createRequest('POST', '/api/login');
        $request = $request->withParsedBody([
            'email' => 'not-an-email',
            'password' => 'password123'
        ]);

        $response = $this->app->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testLoginBadRequestWithMissingPassword(): void
    {
        $request = $this->createRequest('POST', '/api/login');
        $request = $request->withParsedBody([
            'email' => 'test@example.com'
        ]);

        $response = $this->app->handle($request);

        $this->assertEquals(400, $response->getStatusCode());
    }
}
