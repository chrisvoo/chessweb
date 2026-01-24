<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Auth;

use App\Application\Actions\Auth\RefreshTokenAction;
use App\Application\Components\TokenGenerator;
use App\Domain\User\User;
use App\Infrastructure\Components\JWTServiceInterface;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Tests\Helper\Faker;

class RefreshTokenActionApiTest extends TestCase
{
    private function createAction(
        UserRepositoryInterface $userRepo,
        JWTServiceInterface $jwtService,
        TokenGenerator $tokenGenerator
    ): RefreshTokenAction {
        $logger = $this->createMock(LoggerInterface::class);
        return new RefreshTokenAction($userRepo, $logger, $jwtService, $tokenGenerator);
    }

    private function invokeAction(RefreshTokenAction $action): \Psr\Http\Message\ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/refresh');
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

        return $actionMethod->invoke($action);
    }

    public function testRefreshTokenEmptyCookie(): void
    {
        $_COOKIE['rt'] = '';

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $jwtService = $this->createMock(JWTServiceInterface::class);
        $tokenGenerator = $this->createMock(TokenGenerator::class);

        $action = $this->createAction($userRepo, $jwtService, $tokenGenerator);
        $response = $this->invokeAction($action);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertEmpty($body['data']);
    }

    public function testRefreshTokenNoCookie(): void
    {
        unset($_COOKIE['rt']);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $jwtService = $this->createMock(JWTServiceInterface::class);
        $tokenGenerator = $this->createMock(TokenGenerator::class);

        $action = $this->createAction($userRepo, $jwtService, $tokenGenerator);
        $response = $this->invokeAction($action);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertEmpty($body['data']);
    }

    public function testRefreshTokenInvalidToken(): void
    {
        $_COOKIE['rt'] = 'invalid_token';

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $jwtService = $this->createMock(JWTServiceInterface::class);
        $jwtService->method('verifyToken')->willReturn(false);
        $tokenGenerator = $this->createMock(TokenGenerator::class);

        $action = $this->createAction($userRepo, $jwtService, $tokenGenerator);

        $this->expectException(HttpBadRequestException::class);
        $this->invokeAction($action);
    }

    public function testRefreshTokenUserNotFound(): void
    {
        $_COOKIE['rt'] = $this->createValidRefreshToken(999);

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn(false);

        $jwtService = $this->createMock(JWTServiceInterface::class);
        $jwtService->method('verifyToken')->willReturn(true);

        $tokenGenerator = $this->createMock(TokenGenerator::class);

        $action = $this->createAction($userRepo, $jwtService, $tokenGenerator);

        $this->expectException(HttpForbiddenException::class);
        $this->invokeAction($action);
    }

    public function testRefreshTokenSuccess(): void
    {
        $_COOKIE['rt'] = $this->createValidRefreshToken(1);

        $user = Faker::fakeData(User::class);
        $user->id = 1;

        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $userRepo->method('findById')->willReturn($user);

        $jwtService = $this->createMock(JWTServiceInterface::class);
        $jwtService->method('verifyToken')->willReturn(true);

        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenGenerator->method('generateToken')->willReturn([
            'access_token' => 'new_token',
            'expires_in' => 3600
        ]);

        $action = $this->createAction($userRepo, $jwtService, $tokenGenerator);
        $response = $this->invokeAction($action);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('access_token', $body['data']);
        $this->assertEquals('new_token', $body['data']['access_token']);
    }

    /**
     * Helper to create a valid JWT token that can be parsed
     */
    private function createValidRefreshToken(int $userId): string
    {
        $tokenBuilder = new Builder(new JoseEncoder(), ChainedFormatter::default());
        $key = InMemory::plainText('test-secret-key-for-testing-only');
        $now = new \DateTimeImmutable();

        $token = $tokenBuilder
            ->issuedBy('test-issuer')
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('sub_id', $userId)
            ->getToken(new Sha256(), $key);

        return $token->toString();
    }
}
