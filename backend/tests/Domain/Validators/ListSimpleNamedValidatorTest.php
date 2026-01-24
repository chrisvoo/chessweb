<?php

namespace Tests\Domain\Validators;

use App\Domain\DomainException\InvalidRequestException;
use App\Domain\Validators\ListSimpleNamedValidator;
use App\Domain\Validators\ValidationScope;
use Generator;
use Slim\Psr7\Request;
use Tests\Domain\BaseValidator;

class ListSimpleNamedValidatorTest extends BaseValidator
{
    private ListSimpleNamedValidator $validator;

    public function setUp(): void
    {
        parent::setUp();
        $this->validator = new ListSimpleNamedValidator();
    }

    public function testValidateTagsSuccess(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            ['id' => 1, 'name' => 'Chess'],
            ['id' => 2, 'name' => 'Tournament']
        ];

        // Should not throw
        $this->validator->validate($requestMock, $data, ValidationScope::TAGS);
        $this->assertTrue(true);
    }

    public function testValidateCategoriesSuccess(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            ['id' => 1, 'name' => 'Tornei'],
            ['id' => 2, 'name' => 'Comunicazioni']
        ];

        // Should not throw
        $this->validator->validate($requestMock, $data, ValidationScope::CATEGORIES);
        $this->assertTrue(true);
    }

    public function testValidateTagsMissingName(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            ['id' => 1]  // Missing name
        ];

        try {
            $this->validator->validate($requestMock, $data, ValidationScope::TAGS);
            $this->fail('Expected InvalidRequestException was not thrown');
        } catch (InvalidRequestException $e) {
            $this->assertStringContainsString('tag', $e->getMessage());
            $this->assertNotEmpty($e->getExtraDetails());
        }
    }

    public function testValidateCategoriesMissingName(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            ['id' => 1]  // Missing name
        ];

        try {
            $this->validator->validate($requestMock, $data, ValidationScope::CATEGORIES);
            $this->fail('Expected InvalidRequestException was not thrown');
        } catch (InvalidRequestException $e) {
            $this->assertStringContainsString('category', $e->getMessage());
            $this->assertNotEmpty($e->getExtraDetails());
        }
    }

    public function testValidateEmptyName(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            ['id' => 1, 'name' => '']  // Empty name
        ];

        $this->expectException(InvalidRequestException::class);
        $this->validator->validate($requestMock, $data, ValidationScope::TAGS);
    }

    public function testValidateMissingId(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            ['name' => 'Chess']  // Missing id
        ];

        $this->expectException(InvalidRequestException::class);
        $this->validator->validate($requestMock, $data, ValidationScope::TAGS);
    }

    public function testValidateMultipleItemsWithOneInvalid(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            ['id' => 1, 'name' => 'Valid'],
            ['id' => 2, 'name' => '']  // Second item is invalid
        ];

        $this->expectException(InvalidRequestException::class);
        $this->validator->validate($requestMock, $data, ValidationScope::TAGS);
    }
}
