<?php

namespace Tests\Application\Components;

use App\Application\Components\TokenGenerator;
use App\Domain\User\User;
use App\Infrastructure\Components\JWTServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TokenGeneratorTest extends TestCase
{
    private JWTServiceInterface|MockObject $jwtService;
    private TokenGenerator $tokenGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtService = $this->createMock(JWTServiceInterface::class);
        $this->tokenGenerator = new TokenGenerator($this->jwtService);

        // Set required environment variables
        $_ENV['JWT_REFRESH_TTL'] = '86400';
        $_ENV['JWT_TTL'] = '3600';
        $_ENV['JWT_ISSUER'] = 'https://example.com';
        $_ENV['COOKIE_RT_NAME'] = 'refresh_token';
        $_ENV['PRODUCTION'] = 'false';
    }

    public function testGenerateTokenWithRefreshToken(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->first_name = 'Test';
        $user->last_name = 'User';
        $user->password = 'hashed';
        $user->created_at = '2021-01-01 00:00:00';
        $user->updated_at = null;

        $this->jwtService
            ->expects($this->exactly(2))
            ->method('issueToken')
            ->willReturnCallback(function ($user, $isRefresh = false) {
                return $isRefresh ? 'refresh_token_value' : 'access_token_value';
            });

        $result = $this->tokenGenerator->generateToken($user, true);

        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertEquals('access_token_value', $result['access_token']);
        $this->assertEquals(3600, $result['expires_in']);
    }

    public function testGenerateTokenWithoutRefreshToken(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->first_name = 'Test';
        $user->last_name = 'User';
        $user->password = 'hashed';
        $user->created_at = '2021-01-01 00:00:00';
        $user->updated_at = null;

        $this->jwtService
            ->expects($this->exactly(2))
            ->method('issueToken')
            ->willReturnCallback(function ($user, $isRefresh = false) {
                return $isRefresh ? 'refresh_token_value' : 'access_token_value';
            });

        $result = $this->tokenGenerator->generateToken($user, false);

        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertEquals('access_token_value', $result['access_token']);
        $this->assertEquals(3600, $result['expires_in']);
    }

    public function testGenerateTokenReturnsCorrectStructure(): void
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->first_name = 'Test';
        $user->last_name = 'User';
        $user->password = 'hashed';
        $user->created_at = '2021-01-01 00:00:00';
        $user->updated_at = null;

        $this->jwtService
            ->method('issueToken')
            ->willReturn('some_token');

        $result = $this->tokenGenerator->generateToken($user);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
    }

    public function testGenerateTokenExpiresInMatchesEnv(): void
    {
        $_ENV['JWT_TTL'] = '7200';

        $user = new User();
        $user->id = 1;
        $user->email = 'test@example.com';
        $user->first_name = 'Test';
        $user->last_name = 'User';
        $user->password = 'hashed';
        $user->created_at = '2021-01-01 00:00:00';
        $user->updated_at = null;

        $this->jwtService
            ->method('issueToken')
            ->willReturn('token');

        $result = $this->tokenGenerator->generateToken($user);

        $this->assertEquals(7200, $result['expires_in']);
    }
}
