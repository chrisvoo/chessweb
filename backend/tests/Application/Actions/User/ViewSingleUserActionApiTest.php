<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Domain\User\User;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Tests\Helper\Faker;
use Tests\ApiTestCase;

class ViewSingleUserActionApiTest extends ApiTestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testGetSingleUserSuccess(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $user = Faker::fakeData(User::class);
        $repo->method('findById')->willReturn($user);

        $request = $this->createRequest('GET', '/api/user/1');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $user);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testGetSingleUserNotFoundException(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn(false);

        $request = $this->createRequest('GET', '/api/user/1');
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "User not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }
}
