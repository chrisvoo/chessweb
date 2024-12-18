<?php

namespace Infrastructure\Components;

use App\Domain\User\User;
use App\Infrastructure\Components\JWTService;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use DateTimeImmutable;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\Clock\SystemClock;
use Tests\Helper\Faker;
use Tests\TestCase;

class JWTServiceTest extends TestCase
{
    public function testIssueToken(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $user = Faker::fakeData(User::class);
        $repo->method('findById')->willReturn($user);

        $jwtService = new JWTService(
            $repo,
            new FrozenClock(new DateTimeImmutable('2024-12-16 22:13:16'))
        );
        $token = $jwtService->issueToken($user);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testVerifyExpiredToken(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $user = Faker::fakeData(User::class);
        $repo->method('findById')->willReturn($user);
        $jwtService = new JWTService(
            $repo,
            new FrozenClock(new DateTimeImmutable('2024-12-16 22:13:16'))
        );
        $token = $jwtService->issueToken($user);

        $jwtService = new JWTService(
            $repo,
            new SystemClock(new \DateTimeZone('UTC'))
        );
        $this->assertFalse($jwtService->verifyToken($token));
        $this->assertEquals(JWTService::ERROR_EXPIRED, $jwtService->getErrorCode());
    }
}
