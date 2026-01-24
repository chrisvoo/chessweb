<?php

namespace Tests\Application\Actions\Auth;

use App\Application\Actions\Auth\LoginValidator;
use App\Domain\DomainException\InvalidRequestException;
use Generator;
use Slim\Psr7\Request;
use Tests\Domain\BaseValidator;

class LoginValidatorTest extends BaseValidator
{
    private LoginValidator $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = new LoginValidator();
    }

    public function validationDataProvider(): Generator
    {
        yield 'missing email' => [
            ['password' => 'secret123'],
            'email'
        ];

        yield 'empty email' => [
            ['email' => '', 'password' => 'secret123'],
            'email'
        ];

        yield 'invalid email format' => [
            ['email' => 'not-an-email', 'password' => 'secret123'],
            'email'
        ];

        yield 'missing password' => [
            ['email' => 'user@example.com'],
            'password'
        ];

        yield 'empty password' => [
            ['email' => 'user@example.com', 'password' => ''],
            'password'
        ];
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testValidateLogin(array $payload, string $invalidField): void
    {
        parent::testValidate($payload, $invalidField, $this->validator);
    }

    public function testValidateSuccess(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            'email' => 'user@example.com',
            'password' => 'validpassword123'
        ];

        // Should not throw
        $this->validator->validate($requestMock, $data);
        $this->assertTrue(true);
    }

    public function testInvalidRequestExceptionHasDetails(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            'email' => 'invalid',
            'password' => ''
        ];

        try {
            $this->validator->validate($requestMock, $data);
            $this->fail('Expected InvalidRequestException was not thrown');
        } catch (InvalidRequestException $e) {
            $this->assertEquals('Invalid request', $e->getMessage());
            $this->assertNotEmpty($e->getExtraDetails());
        }
    }
}
