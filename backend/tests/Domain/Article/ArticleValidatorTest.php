<?php

namespace Tests\Domain\Article;

use App\Domain\Article\ArticleValidator;
use App\Domain\DomainException\InvalidRequestException;
use App\Domain\Validators\ListSimpleNamedValidator;
use App\Domain\Validators\ValidationScope;
use Generator;
use Slim\Psr7\Request;
use Tests\Domain\BaseValidator;

class ArticleValidatorTest extends BaseValidator
{
    private ArticleValidator $articleValidator;

    public function setUp(): void
    {
        parent::setUp();
        $this->articleValidator = new ArticleValidator(new ListSimpleNamedValidator());
    }

    public function validationDataProvider(): Generator
    {
        yield 'missing title' => [
            [
                'content' => 'Some content'
            ],
            'title',
            null
        ];

        yield 'empty title' => [
            [
                'title' => '',
                'content' => 'Some content'
            ],
            'title',
            null
        ];

        yield 'missing content' => [
            [
                'title' => 'A title'
            ],
            'content',
            null
        ];

        yield 'empty content' => [
            [
                'title' => 'A title',
                'content' => ''
            ],
            'content',
            null
        ];

        yield 'missing id on update' => [
            [
                'title' => 'A title',
                'content' => 'Some content'
            ],
            'id',
            ValidationScope::UPDATE
        ];
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testValidateArticle(
        array $payload,
        string $invalidField,
        ?ValidationScope $scope
    ): void {
        parent::testValidate($payload, $invalidField, $this->articleValidator, $scope);
    }

    public function testValidateWithInvalidTags(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $payload = [
            'title' => 'A valid title',
            'content' => 'Valid content',
            'tags' => [
                ['name' => ''] // Invalid: empty name
            ]
        ];

        $this->expectException(InvalidRequestException::class);
        $this->articleValidator->validate($requestMock, $payload);
    }

    public function testValidateWithInvalidCategories(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $payload = [
            'title' => 'A valid title',
            'content' => 'Valid content',
            'categories' => [
                ['name' => ''] // Invalid: empty name
            ]
        ];

        $this->expectException(InvalidRequestException::class);
        $this->articleValidator->validate($requestMock, $payload);
    }

    public function testValidateWithValidTagsAndCategories(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $payload = [
            'title' => 'A valid title',
            'content' => 'Valid content',
            'tags' => [
                ['id' => 1, 'name' => 'Chess']
            ],
            'categories' => [
                ['id' => 1, 'name' => 'Tornei']
            ]
        ];

        // Should not throw
        $this->articleValidator->validate($requestMock, $payload);
        $this->assertTrue(true);
    }

    public function testInvalidRequestExceptionHasExtraDetails(): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $payload = [
            'title' => '',
            'content' => ''
        ];

        try {
            $this->articleValidator->validate($requestMock, $payload);
            $this->fail('Expected InvalidRequestException was not thrown');
        } catch (InvalidRequestException $e) {
            $this->assertNotEmpty($e->getExtraDetails());
            $this->assertIsArray($e->getExtraDetails());
        }
    }
}
