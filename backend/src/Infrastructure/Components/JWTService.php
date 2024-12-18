<?php

namespace App\Infrastructure\Components;

use App\Domain\User\User;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use DateTimeImmutable;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\HasClaim;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\Validator;
use Psr\Clock\ClockInterface;

class JWTService implements JWTServiceInterface
{
    public const ERROR_INVALID_ISSUER = 1;
    public const ERROR_INVALID_SUGNTURE = 2;
    public const ERROR_EXPIRED = 3;
    public const ERROR_MISSING_CLAIM = 3;

    private int $errorCode = 0;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private readonly ClockInterface $clock
    ) {
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * Issue a JWT token
     * @return string JWT token
     */
    public function issueToken(User $user, bool $isRefresh = false): string
    {
        $key = InMemory::plainText($_ENV['JWT_SECRET']);
        $expirationSeconds = $isRefresh ? $_ENV['JWT_REFRESH_TTL'] : $_ENV['JWT_TTL'];

        return (new JwtFacade(new Parser(new JoseEncoder()), $this->clock))->issue(
            new Sha256(),
            $key,
            static fn (
                Builder $builder,
                DateTimeImmutable $issuedAt,
            ): Builder => $builder
                ->issuedBy($_ENV['JWT_ISSUER'])
                ->expiresAt($issuedAt->modify('+' . $expirationSeconds . ' seconds'))
                ->withClaim('sub_id', $user->id)
        )->toString();
    }

    /**
     * It verifies a JWT token
     * @param string $token JWT token
     * @return User|bool The user that the token belongs to or false
     */
    public function verifyToken(string $token): User|bool
    {
        $parser = new Parser(new JoseEncoder());
        $tokenObject = $parser->parse($token);

        $validator = new Validator();
        if (!$validator->validate($tokenObject, new IssuedBy($_ENV['JWT_ISSUER']))) {
            $this->errorCode = self::ERROR_INVALID_ISSUER;
            return false;
        }

        if (
            !$validator->validate(
                $tokenObject,
                new SignedWith(new Sha256(), InMemory::plainText($_ENV['JWT_SECRET']))
            )
        ) {
            $this->errorCode = self::ERROR_INVALID_SUGNTURE;
            return false;
        }

        if (!$validator->validate($tokenObject, new StrictValidAt($this->clock))) {
            $this->errorCode = self::ERROR_EXPIRED;
            return false;
        }

        if (!$validator->validate($tokenObject, new HasClaim('sub_id'))) {
            $this->errorCode = self::ERROR_MISSING_CLAIM;
            return false;
        }

        assert($tokenObject instanceof UnencryptedToken);

        $userId = (int)$tokenObject->claims()->get('sub_id');
        $user = $this->userRepository->findById($userId);

        if (empty($user) || !$user->valid) {
            return false;
        }

        return $user;
    }
}
