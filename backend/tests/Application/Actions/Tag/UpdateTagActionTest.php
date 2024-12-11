<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Tag;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Domain\Operations\DatabaseOperation;
use App\Domain\Tag\Tag;
use App\Domain\Tag\TagNotFoundException;
use App\Domain\User\User;
use App\Domain\User\UserNotFoundException;
use App\Infrastructure\Persistence\Tag\TagRepositoryInterface;
use App\Infrastructure\Persistence\User\UserRepositoryInterface;
use Tests\Helper\Faker;
use Tests\TestCase;

class UpdateTagActionTest extends TestCase
{
    private function getFakeTag(): array
    {
        $tag = Faker::fakeData(Tag::class)->jsonSerialize();
        unset($tag['created_at']);
        unset($tag['updated_at']);
        unset($tag['id']);

        return $tag;
    }

    /**
     * @throws \ReflectionException
     */
    public function testUpdateTagSuccess(): void
    {
        $repo = $this->mockRepository(TagRepositoryInterface::class);
        $dbOp = DatabaseOperation::newSingleEntitySuccessfullyUpdated(1);
        $repo->method('save')->willReturn($dbOp);

        $request = $this->createRequest(
            'PUT',
            '/api/tag/1'
        )->withParsedBody($this->getFakeTag());

        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, $dbOp);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testUpdateTagNotFoundException(): void
    {
        $repo = $this->mockRepository(TagRepositoryInterface::class);
        $repo->method('save')->willThrowException(new TagNotFoundException());

        $request = $this->createRequest(
            'PUT',
            '/api/tag/1'
        )->withParsedBody($this->getFakeTag());
        $response = $this->app->handle($request);
        $payload = (string) $response->getBody();

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, "Tag not found");
        $expectedPayload = new ActionPayload(404, null, $expectedError);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);
        $this->assertEquals($serializedPayload, $payload);
    }
}
