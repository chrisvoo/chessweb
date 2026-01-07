<?php

namespace Tests\Domain;

use App\Domain\DomainException\InvalidRequestException;
use App\Domain\Validators\ValidationScope;
use App\Domain\Validators\ValidatorInterface;
use Slim\Psr7\Request;
use Tests\ApiTestCase;

class BaseValidator extends ApiTestCase
{
    /**
     * @param array $payload
     * @param string $invalidField
     * @param ValidatorInterface $class
     * @param ValidationScope|null $scope
     * @return void
     */
    protected function testValidate(
        array $payload,
        string $invalidField,
        ValidatorInterface $class,
        ?ValidationScope $scope = null
    ): void
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $triggered = false;
        try {
            $class->validate($requestMock, $payload, $scope);
        } catch (InvalidRequestException $e) {
            $triggered = true;
            $details = $e->getExtraDetails();
            $this->assertContains($invalidField, array_keys($details));
        }

        $this->assertTrue($triggered);
    }
}
