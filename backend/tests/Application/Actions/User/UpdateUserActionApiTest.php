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

class UpdateUserActionApiTest extends ApiTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testUpdateUserSuccess(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated(1);
        $repo->method('save')->willReturn($dbOp);

        $request = $this->createRequest(
            'PUT',
            '/api/user/1'
        )->withParsedBody((Faker::fakeData(User::class))->jsonSerialize());

        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $dbOp);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testUpdateUserNotFoundException(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $repo->method('save')->willThrowException(new UserNotFoundException());

        $request = $this->createRequest(
            'PUT',
            '/api/user/1'
        )->withParsedBody(Faker::fakeData(User::class)->jsonSerialize());
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "User not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }
}
