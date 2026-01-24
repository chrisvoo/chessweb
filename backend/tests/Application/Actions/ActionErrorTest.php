<?php

declare(strict_types=1);

namespace Tests\Application\Actions;

use App\Application\Actions\ActionError;
use PHPUnit\Framework\TestCase;

class ActionErrorTest extends TestCase
{
    public function testConstructor(): void
    {
        $error = new ActionError(ActionError::BAD_REQUEST, 'Test description');

        $this->assertEquals(ActionError::BAD_REQUEST, $error->getType());
        $this->assertEquals('Test description', $error->getDescription());
    }

    public function testConstructorWithNullDescription(): void
    {
        $error = new ActionError(ActionError::SERVER_ERROR);

        $this->assertEquals(ActionError::SERVER_ERROR, $error->getType());
        $this->assertNull($error->getDescription());
    }

    public function testSetType(): void
    {
        $error = new ActionError(ActionError::BAD_REQUEST);
        $result = $error->setType(ActionError::RESOURCE_NOT_FOUND);

        $this->assertSame($error, $result);
        $this->assertEquals(ActionError::RESOURCE_NOT_FOUND, $error->getType());
    }

    public function testSetDescription(): void
    {
        $error = new ActionError(ActionError::BAD_REQUEST);
        $result = $error->setDescription('New description');

        $this->assertSame($error, $result);
        $this->assertEquals('New description', $error->getDescription());
    }

    public function testSetDescriptionNull(): void
    {
        $error = new ActionError(ActionError::BAD_REQUEST, 'Initial');
        $error->setDescription(null);

        $this->assertNull($error->getDescription());
    }

    public function testJsonSerialize(): void
    {
        $error = new ActionError(ActionError::VALIDATION_ERROR, 'Validation failed');

        $expected = [
            'type' => ActionError::VALIDATION_ERROR,
            'description' => 'Validation failed'
        ];

        $this->assertEquals($expected, $error->jsonSerialize());
    }

    public function testJsonSerializeWithNullDescription(): void
    {
        $error = new ActionError(ActionError::UNAUTHENTICATED);

        $expected = [
            'type' => ActionError::UNAUTHENTICATED,
            'description' => null
        ];

        $this->assertEquals($expected, $error->jsonSerialize());
    }

    public function testJsonEncode(): void
    {
        $error = new ActionError(ActionError::RESOURCE_NOT_FOUND, 'Not found');

        $expectedJson = json_encode([
            'type' => ActionError::RESOURCE_NOT_FOUND,
            'description' => 'Not found'
        ]);

        $this->assertEquals($expectedJson, json_encode($error));
    }

    public function testErrorTypeConstants(): void
    {
        $this->assertEquals('BAD_REQUEST', ActionError::BAD_REQUEST);
        $this->assertEquals('INSUFFICIENT_PRIVILEGES', ActionError::INSUFFICIENT_PRIVILEGES);
        $this->assertEquals('NOT_ALLOWED', ActionError::NOT_ALLOWED);
        $this->assertEquals('NOT_IMPLEMENTED', ActionError::NOT_IMPLEMENTED);
        $this->assertEquals('RESOURCE_NOT_FOUND', ActionError::RESOURCE_NOT_FOUND);
        $this->assertEquals('SERVER_ERROR', ActionError::SERVER_ERROR);
        $this->assertEquals('UNAUTHENTICATED', ActionError::UNAUTHENTICATED);
        $this->assertEquals('VALIDATION_ERROR', ActionError::VALIDATION_ERROR);
        $this->assertEquals('VERIFICATION_ERROR', ActionError::VERIFICATION_ERROR);
    }
}
