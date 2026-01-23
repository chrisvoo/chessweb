<?php

namespace Tests\Infrastructure\Components;

use App\Domain\User\User;
use App\Infrastructure\Components\JWTService;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use DateTimeImmutable;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Parser;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Helper\Faker;
use Tests\ApiTestCase;

class JWTServiceTest extends ApiTestCase
{
    private FrozenClock $frozenClock;
    private UserRepositoryInterface|MockObject $userRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->frozenClock = new FrozenClock(new DateTimeImmutable('2024-12-16 22:13:16'));
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
    }

    public function testIssueToken(): void
    {
        $user = Faker::fakeData(User::class);
        $this->userRepository->method('findById')->willReturn($user);

        $jwtService = new JWTService($this->userRepository, $this->frozenClock);
        $token = $jwtService->issueToken($user);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testIssueRefreshToken(): void
    {
        $user = Faker::fakeData(User::class);
        $this->userRepository->method('findById')->willReturn($user);

        $jwtService = new JWTService($this->userRepository, $this->frozenClock);
        $token = $jwtService->issueToken($user, true);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testVerifyTokenSuccess(): void
    {
        $user = Faker::fakeData(User::class);
        $user->valid = true;
        $this->userRepository->method('findById')->willReturn($user);

        $jwtService = new JWTService($this->userRepository, $this->frozenClock);
        $token = $jwtService->issueToken($user);

        // Verify with the same frozen clock (token is not expired)
        $result = $jwtService->verifyToken($token);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    public function testVerifyExpiredToken(): void
    {
        $user = Faker::fakeData(User::class);
        $this->userRepository->method('findById')->willReturn($user);

        $jwtService = new JWTService($this->userRepository, $this->frozenClock);
        $token = $jwtService->issueToken($user);

        // Verify with current time (token from 2024 is expired)
        $jwtService = new JWTService(
            $this->userRepository,
            new SystemClock(new \DateTimeZone('UTC'))
        );
        $this->assertFalse($jwtService->verifyToken($token));
        $this->assertEquals(JWTService::ERROR_EXPIRED, $jwtService->getErrorCode());
    }

    public function testVerifyTokenInvalidIssuer(): void
    {
        // Create a token with a different issuer
        $token = $this->createTokenWithCustomIssuer('wrong-issuer');

        $jwtService = new JWTService($this->userRepository, $this->frozenClock);
        $result = $jwtService->verifyToken($token);

        $this->assertFalse($result);
        $this->assertEquals(JWTService::ERROR_INVALID_ISSUER, $jwtService->getErrorCode());
    }

    public function testVerifyTokenInvalidSignature(): void
    {
        // Create a token signed with a different secret
        $token = $this->createTokenWithDifferentSecret();

        $jwtService = new JWTService($this->userRepository, $this->frozenClock);
        $result = $jwtService->verifyToken($token);

        $this->assertFalse($result);
        $this->assertEquals(JWTService::ERROR_INVALID_SUGNTURE, $jwtService->getErrorCode());
    }

    public function testVerifyTokenMissingClaim(): void
    {
        // Create a token without the sub_id claim
        $token = $this->createTokenWithoutSubIdClaim();

        $jwtService = new JWTService($this->userRepository, $this->frozenClock);
        $result = $jwtService->verifyToken($token);

        $this->assertFalse($result);
        $this->assertEquals(JWTService::ERROR_MISSING_CLAIM, $jwtService->getErrorCode());
    }

    public function testVerifyTokenUserNotFound(): void
    {
        $user = Faker::fakeData(User::class);
        $user->valid = true;

        // First call to issue token returns user, second call (verify) returns false
        $this->userRepository->method('findById')->willReturn(false);

        $jwtService = new JWTService($this->userRepository, $this->frozenClock);

        // Create a valid token manually since issueToken doesn't use findById
        $token = $jwtService->issueToken($user);

        $result = $jwtService->verifyToken($token);

        $this->assertFalse($result);
    }

    public function testVerifyTokenUserNotValid(): void
    {
        $user = Faker::fakeData(User::class);
        $user->valid = true;

        $jwtService = new JWTService($this->userRepository, $this->frozenClock);
        $token = $jwtService->issueToken($user);

        // Now make user invalid for verification
        $invalidUser = Faker::fakeData(User::class);
        $invalidUser->id = $user->id;
        $invalidUser->valid = false;

        $this->userRepository->method('findById')->willReturn($invalidUser);

        $jwtService2 = new JWTService($this->userRepository, $this->frozenClock);
        $result = $jwtService2->verifyToken($token);

        $this->assertFalse($result);
    }

    public function testGetErrorCodeInitiallyZero(): void
    {
        $jwtService = new JWTService($this->userRepository, $this->frozenClock);
        $this->assertEquals(0, $jwtService->getErrorCode());
    }

    /**
     * Helper: Create a token with a custom issuer
     */
    private function createTokenWithCustomIssuer(string $issuer): string
    {
        $tokenBuilder = new Builder(new JoseEncoder(), ChainedFormatter::default());
        $key = InMemory::plainText($_ENV['JWT_SECRET']);
        $now = $this->frozenClock->now();

        $token = $tokenBuilder
            ->issuedBy($issuer)
            ->issuedAt($now->modify('-10 seconds'))
            ->expiresAt($now->modify('+3600 seconds'))
            ->withClaim('sub_id', 123)
            ->getToken(new Sha256(), $key);

        return $token->toString();
    }

    /**
     * Helper: Create a token signed with a different secret
     */
    private function createTokenWithDifferentSecret(): string
    {
        $tokenBuilder = new Builder(new JoseEncoder(), ChainedFormatter::default());
        $wrongKey = InMemory::plainText('wrong-secret-key-that-is-different');
        $now = $this->frozenClock->now();

        $token = $tokenBuilder
            ->issuedBy($_ENV['JWT_ISSUER'])
            ->issuedAt($now->modify('-10 seconds'))
            ->expiresAt($now->modify('+3600 seconds'))
            ->withClaim('sub_id', 123)
            ->getToken(new Sha256(), $wrongKey);

        return $token->toString();
    }

    /**
     * Helper: Create a token without the sub_id claim
     * Uses JwtFacade (same as issueToken) to ensure token passes all validation except HasClaim
     */
    private function createTokenWithoutSubIdClaim(): string
    {
        $key = InMemory::plainText($_ENV['JWT_SECRET']);

        return (new JwtFacade(new Parser(new JoseEncoder()), $this->frozenClock))->issue(
            new Sha256(),
            $key,
            static fn (
                Builder $builder,
                DateTimeImmutable $issuedAt,
            ): Builder => $builder
                ->issuedBy($_ENV['JWT_ISSUER'])
                ->expiresAt($issuedAt->modify('+3600 seconds'))
                // Intentionally NOT adding sub_id claim
        )->toString();
    }
}
