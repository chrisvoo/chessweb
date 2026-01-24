<?php

declare(strict_types=1);

namespace Tests\Domain\User;

use App\Domain\User\User;
use DomainException;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $id = 1;
        $email = 'user@example.com';
        $firstName = 'John';
        $password = 'sadasdasd';
        $lastName = 'Doe';
        $isAdmin = true;
        $createdAt = '2021-01-01 00:00:00';
        $updatedAt = '2021-01-01 00:00:00';

        $user = new User();
        $user->id = $id;
        $user->email = $email;
        $user->password = $password;
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->is_admin = $isAdmin;
        $user->created_at = $createdAt;
        $user->updated_at = $updatedAt;

        $expectedPayload = json_encode([
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'is_admin' => $isAdmin,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        $this->assertJsonStringEqualsJsonString($expectedPayload, json_encode($user));
    }

    public function testTableName(): void
    {
        $this->assertEquals('users', User::TABLE_NAME);
    }

    public function testHashPassword(): void
    {
        $user = new User();
        $user->password = 'mySecurePassword123';

        $result = $user->hashPassword();

        $this->assertSame($user, $result);
        $this->assertTrue(password_verify('mySecurePassword123', $user->password));
    }

    public function testHashPasswordWithEmptyPassword(): void
    {
        $user = new User();
        $user->password = '';

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Password is required.');

        $user->hashPassword();
    }

    public function testHashPasswordWithUnsetPassword(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Password is required.');

        $user = new User();
        $user->hashPassword();
    }

    public function testDefaultValues(): void
    {
        $user = new User();

        $this->assertFalse($user->is_admin);
        $this->assertTrue($user->valid);
    }
}
