<?php

declare(strict_types=1);

namespace Tests\Application\Actions\Health;

use App\Application\Actions\ActionPayload;
use Tests\ApiTestCase;

class StatusActionApiTest extends ApiTestCase
{
    public function testStatusAction(): void
    {
        $request = $this->createRequest('GET', '/api/status');
        $response = $this->app->handle($request);

        $payload = (string) $response->getBody();
        $expectedPayload = new ActionPayload(200, ['status' => 'OK']);
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testStatusActionReturns200(): void
    {
        $request = $this->createRequest('GET', '/api/status');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testStatusActionReturnsJson(): void
    {
        $request = $this->createRequest('GET', '/api/status');
        $response = $this->app->handle($request);

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('status', $payload['data']);
        $this->assertEquals('OK', $payload['data']['status']);
    }
}
