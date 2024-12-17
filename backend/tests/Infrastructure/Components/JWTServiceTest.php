<?php

namespace Infrastructure\Components;

use App\Domain\User\User;
use App\Infrastructure\Components\JWTService;
use DateTimeImmutable;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\Clock\SystemClock;
use PHPUnit\Framework\TestCase;

class JWTServiceTest extends TestCase
{
    private function issueToken(JWTService $service): string
    {
        $user = new User();
        $user->id = 1;
        $user->email = 'fV2Tt@example.com';
        $user->first_name = 'John';
        $user->last_name = 'Doe';

        return $service->issueToken($user);
    }

    public function testIssueToken(): void
    {
        $jwtService = new JWTService(new FrozenClock(new DateTimeImmutable('2024-12-16 22:13:16')));
        $token = $this->issueToken($jwtService);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testVerifyExpiredToken(): void
    {
        $jwtService = new JWTService(new FrozenClock(new DateTimeImmutable('2024-12-16 22:13:16')));
        $token = $this->issueToken($jwtService);

        $jwtService = new JWTService(new SystemClock(new \DateTimeZone('UTC')));
        $this->assertFalse($jwtService->verifyToken($token));
        $this->assertEquals(JWTService::ERROR_EXPIRED, $jwtService->getErrorCode());
    }
}
