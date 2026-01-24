<?php

declare(strict_types=1);

namespace Tests\Application\Actions;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use PHPUnit\Framework\TestCase;
use stdClass;

class ActionPayloadTest extends TestCase
{
    public function testConstructorDefaults(): void
    {
        $payload = new ActionPayload();

        $this->assertEquals(200, $payload->getStatusCode());
        $this->assertNull($payload->getData());
        $this->assertNull($payload->getError());
    }

    public function testConstructorWithAllParameters(): void
    {
        $error = new ActionError(ActionError::BAD_REQUEST, 'Error');
        $data = ['key' => 'value'];
        $payload = new ActionPayload(400, $data, $error);

        $this->assertEquals(400, $payload->getStatusCode());
        $this->assertEquals($data, $payload->getData());
        $this->assertSame($error, $payload->getError());
    }

    public function testGetStatusCode(): void
    {
        $payload = new ActionPayload(201);

        $this->assertEquals(201, $payload->getStatusCode());
    }

    public function testGetDataWithArray(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $payload = new ActionPayload(200, $data);

        $this->assertEquals($data, $payload->getData());
    }

    public function testGetDataWithObject(): void
    {
        $data = new stdClass();
        $data->id = 1;
        $payload = new ActionPayload(200, $data);

        $this->assertSame($data, $payload->getData());
    }

    public function testGetError(): void
    {
        $error = new ActionError(ActionError::SERVER_ERROR);
        $payload = new ActionPayload(500, null, $error);

        $this->assertSame($error, $payload->getError());
    }

    public function testJsonSerializeWithOnlyStatusCode(): void
    {
        $payload = new ActionPayload(200);

        $expected = [
            'statusCode' => 200
        ];

        $this->assertEquals($expected, $payload->jsonSerialize());
    }

    public function testJsonSerializeWithData(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $payload = new ActionPayload(200, $data);

        $expected = [
            'statusCode' => 200,
            'data' => $data
        ];

        $this->assertEquals($expected, $payload->jsonSerialize());
    }

    public function testJsonSerializeWithError(): void
    {
        $error = new ActionError(ActionError::BAD_REQUEST, 'Invalid input');
        $payload = new ActionPayload(400, null, $error);

        $expected = [
            'statusCode' => 400,
            'error' => $error
        ];

        $this->assertEquals($expected, $payload->jsonSerialize());
    }

    public function testJsonSerializeWithDataAndError(): void
    {
        $data = ['partial' => 'data'];
        $error = new ActionError(ActionError::VALIDATION_ERROR, 'Partial error');
        $payload = new ActionPayload(422, $data, $error);

        $expected = [
            'statusCode' => 422,
            'data' => $data,
            'error' => $error
        ];

        $this->assertEquals($expected, $payload->jsonSerialize());
    }

    public function testJsonEncode(): void
    {
        $data = ['id' => 1];
        $payload = new ActionPayload(200, $data);

        $expectedJson = json_encode([
            'statusCode' => 200,
            'data' => ['id' => 1]
        ]);

        $this->assertEquals($expectedJson, json_encode($payload));
    }
}
