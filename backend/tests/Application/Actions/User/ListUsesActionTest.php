<?php

namespace Tests\Application\Actions\User;

use App\Application\Actions\ActionPayload;
use App\Domain\User\User;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Tests\Helper\Faker;
use Tests\TestCase;

class ListUsesActionTest extends TestCase
{
    public function testUsersListSuccess(): void
    {
        $repo = $this->mockRepository(UserRepositoryInterface::class);
        $user = Faker::fakeData(User::class);
        $repo->method('findAll')->willReturn([$user]);

        $request = $this->createRequest('GET', '/api/users');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, [$user]);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
