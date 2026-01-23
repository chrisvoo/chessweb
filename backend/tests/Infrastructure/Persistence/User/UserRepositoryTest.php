<?php

namespace Tests\Infrastructure\Persistence\User;

use App\Domain\Operations\DatabaseOperation;
use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Infrastructure\Persistence\DatabaseManagerInterface;
use App\Infrastructure\Persistence\User\UserRepository;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Tests\IntegrationTestCase;

class UserRepositoryTest extends IntegrationTestCase
{
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository(
            $this->container->get(DatabaseManagerInterface::class),
            $this->container->get(LoggerInterface::class)
        );
    }

    public function testFindAll(): void
    {
        $users = $this->userRepository->findAll();
        $this->assertIsArray($users);
        $this->assertNotEmpty($users);
        $this->assertContainsOnlyInstancesOf(User::class, $users);
    }

    public function testFindById(): void
    {
        $user = $this->userRepository->findById(1);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(1, $user->id);
        $this->assertNotEmpty($user->email);
        $this->assertNotEmpty($user->first_name);
        $this->assertNotEmpty($user->last_name);
    }

    public function testFindByIdNotFound(): void
    {
        $user = $this->userRepository->findById(99999);
        $this->assertFalse($user);
    }

    public function testUpdateUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);

        $user = new User();
        $user->id = 99999;
        $user->email = 'nonexistent@example.com';
        $user->first_name = 'Non';
        $user->last_name = 'Existent';
        $user->is_admin = false;
        $user->valid = true;
        $this->userRepository->save($user);
    }

    public function testUpdateSuccess(): void
    {
        // First, get the existing user
        $existingUser = $this->userRepository->findById(1);
        $originalFirstName = $existingUser->first_name;

        // Update the user
        $user = new User();
        $user->id = 1;
        $user->email = $existingUser->email;
        $user->first_name = 'UpdatedFirstName';
        $user->last_name = $existingUser->last_name;
        $user->is_admin = $existingUser->is_admin;
        $user->valid = $existingUser->valid;

        $op = $this->userRepository->save($user);
        $this->assertEquals(DatabaseOperation::ENTITY_UPDATED, $op->code);
        $this->assertEquals(1, $op->affectedRows);

        // Verify the update
        $updatedUser = $this->userRepository->findById(1);
        $this->assertEquals('UpdatedFirstName', $updatedUser->first_name);
    }

    public function testInsertSuccess(): void
    {
        $user = new User();
        $user->email = 'newuser@example.com';
        $user->first_name = 'New';
        $user->last_name = 'User';
        $user->password = password_hash('password123', PASSWORD_DEFAULT);
        $user->is_admin = false;
        $user->valid = true;

        $op = $this->userRepository->save($user);
        $this->assertEquals(DatabaseOperation::ENTITY_CREATED, $op->code);
        $this->assertIsInt($op->entityId);

        // Verify the inserted user exists
        $insertedUser = $this->userRepository->findById($op->entityId);
        $this->assertInstanceOf(User::class, $insertedUser);
        $this->assertEquals('newuser@example.com', $insertedUser->email);
        $this->assertEquals('New', $insertedUser->first_name);
        $this->assertEquals('User', $insertedUser->last_name);
    }

    public function testInsertAdminUser(): void
    {
        $user = new User();
        $user->email = 'admin@example.com';
        $user->first_name = 'Admin';
        $user->last_name = 'User';
        $user->password = password_hash('adminpass', PASSWORD_DEFAULT);
        $user->is_admin = true;
        $user->valid = true;

        $op = $this->userRepository->save($user);
        $this->assertEquals(DatabaseOperation::ENTITY_CREATED, $op->code);

        $insertedUser = $this->userRepository->findById($op->entityId);
        $this->assertTrue($insertedUser->is_admin);
    }

    public function testDeleteById(): void
    {
        // First create a user to delete
        $user = new User();
        $user->email = 'todelete@example.com';
        $user->first_name = 'To';
        $user->last_name = 'Delete';
        $user->password = password_hash('password', PASSWORD_DEFAULT);
        $user->is_admin = false;
        $user->valid = true;

        $createOp = $this->userRepository->save($user);
        $userId = $createOp->entityId;

        // Now delete the user
        $op = $this->userRepository->delete($userId);
        $this->assertEquals(DatabaseOperation::ENTITY_DELETED, $op->code);
        $this->assertEquals(1, $op->affectedRows);

        // Verify deletion
        $deletedUser = $this->userRepository->findById($userId);
        $this->assertFalse($deletedUser);
    }

    public function testDeleteNonExistentUser(): void
    {
        $op = $this->userRepository->delete(99999);
        $this->assertEquals(DatabaseOperation::ENTITY_DELETED, $op->code);
        $this->assertEquals(0, $op->affectedRows);
    }

    public function testLoginSuccess(): void
    {
        // Create a user with known credentials
        $plainPassword = 'testpassword123';
        $user = new User();
        $user->email = 'logintest@example.com';
        $user->first_name = 'Login';
        $user->last_name = 'Test';
        $user->password = password_hash($plainPassword, PASSWORD_DEFAULT);
        $user->is_admin = false;
        $user->valid = true;

        $createOp = $this->userRepository->save($user);
        $this->assertEquals(DatabaseOperation::ENTITY_CREATED, $createOp->code);

        // Test login with correct credentials
        $loggedInUser = $this->userRepository->login('logintest@example.com', $plainPassword);
        $this->assertInstanceOf(User::class, $loggedInUser);
        $this->assertEquals('logintest@example.com', $loggedInUser->email);
    }

    public function testLoginWrongPassword(): void
    {
        // Create a user with known credentials
        $user = new User();
        $user->email = 'wrongpasstest@example.com';
        $user->first_name = 'Wrong';
        $user->last_name = 'Password';
        $user->password = password_hash('correctpassword', PASSWORD_DEFAULT);
        $user->is_admin = false;
        $user->valid = true;

        $this->userRepository->save($user);

        // Test login with wrong password
        $result = $this->userRepository->login('wrongpasstest@example.com', 'wrongpassword');
        $this->assertFalse($result);
    }

    public function testLoginNonExistentEmail(): void
    {
        $result = $this->userRepository->login('nonexistent@example.com', 'anypassword');
        $this->assertFalse($result);
    }

    public function testLoginEmptyPassword(): void
    {
        // Create a user
        $user = new User();
        $user->email = 'emptypasstest@example.com';
        $user->first_name = 'Empty';
        $user->last_name = 'Pass';
        $user->password = password_hash('realpassword', PASSWORD_DEFAULT);
        $user->is_admin = false;
        $user->valid = true;

        $this->userRepository->save($user);

        // Test login with empty password
        $result = $this->userRepository->login('emptypasstest@example.com', '');
        $this->assertFalse($result);
    }
}
