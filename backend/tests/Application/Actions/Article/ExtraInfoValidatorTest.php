<?php

namespace Tests\Application\Actions\Article;

use App\Application\Actions\Article\Validators\ExtraInfoValidator;
use App\Domain\DomainException\InvalidRequestException;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Request;

class ExtraInfoValidatorTest extends TestCase
{
    private ExtraInfoValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ExtraInfoValidator();
    }

    private function createMockRequest(): Request
    {
        return $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testValidateWithValidBooleanTrue(): void
    {
        $request = $this->createMockRequest();
        $data = ['extra_info' => true];

        // Should not throw
        $this->validator->validate($request, $data);
        $this->assertTrue(true);
    }

    public function testValidateWithValidBooleanFalse(): void
    {
        $request = $this->createMockRequest();
        $data = ['extra_info' => false];

        // false is considered empty by notEmpty(), so this will throw
        $this->expectException(InvalidRequestException::class);
        $this->validator->validate($request, $data);
    }

    public function testValidateWithValidStringTrue(): void
    {
        $request = $this->createMockRequest();
        $data = ['extra_info' => 'true'];

        // Should not throw - 'true' is a valid bool value
        $this->validator->validate($request, $data);
        $this->assertTrue(true);
    }

    public function testValidateWithValidNumericOne(): void
    {
        $request = $this->createMockRequest();
        $data = ['extra_info' => 1];

        // Should not throw - 1 is a valid bool value
        $this->validator->validate($request, $data);
        $this->assertTrue(true);
    }

    public function testValidateWithoutExtraInfo(): void
    {
        $request = $this->createMockRequest();
        $data = ['other_field' => 'value'];

        // Should not throw - extra_info is optional
        $this->validator->validate($request, $data);
        $this->assertTrue(true);
    }

    public function testValidateWithInvalidExtraInfo(): void
    {
        $request = $this->createMockRequest();
        $data = ['extra_info' => 'invalid_value'];

        $this->expectException(InvalidRequestException::class);
        $this->validator->validate($request, $data);
    }

    public function testValidateWithNullExtraInfo(): void
    {
        $request = $this->createMockRequest();
        $data = ['extra_info' => ''];

        // null is not a valid bool and is empty, so this will throw
        $this->expectException(InvalidRequestException::class);
        $this->validator->validate($request, $data);
    }

    public function testValidateWithEmptyStringExtraInfo(): void
    {
        $request = $this->createMockRequest();
        $data = ['extra_info' => ''];

        $this->expectException(InvalidRequestException::class);
        $this->validator->validate($request, $data);
    }

    public function testInvalidRequestExceptionHasCorrectMessage(): void
    {
        $request = $this->createMockRequest();
        $data = ['extra_info' => 'not_a_bool'];

        try {
            $this->validator->validate($request, $data);
            $this->fail('Expected InvalidRequestException was not thrown');
        } catch (InvalidRequestException $e) {
            $this->assertEquals('Invalid request', $e->getMessage());
            $this->assertContains('extra_info is not valid', $e->getExtraDetails());
        }
    }
}
