<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Tests\Helper\Faker;
use Tests\ApiTestCase;

class DeleteUserActionApiTest extends ApiTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testGetSingleUserSuccess(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted(1);
        $repo->method('delete')->willReturn($dbOp);

        $request = $this->createRequest('DELETE', '/api/user/1');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $dbOp);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testGetSingleUserNotFoundException(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $repo->method('delete')->willThrowException(new UserNotFoundException());

        $request = $this->createRequest('DELETE', '/api/user/1');
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "User not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }

    public function testDeleteUserNotFoundWhenAffectedRowsZero(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);

        // Create a DatabaseOperation with affectedRows = 0
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyDeleted(999);
        $dbOp->affectedRows = 0;

        $repo->method('delete')->willReturn($dbOp);

        $request = $this->createRequest('DELETE', '/api/user/999');
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "User not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }
}
