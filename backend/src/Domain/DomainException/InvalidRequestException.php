<?php

namespace App\Domain\DomainException;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

class InvalidRequestException extends HttpBadRequestException
{
    private array $extraDetails;

    public function __construct(
        ServerRequestInterface $request,
        string $message,
        array $extraDetails = []
    ) {
        parent::__construct($request, $message);
        $this->extraDetails = $extraDetails;
    }

    /**
     * @return array
     */
    public function getExtraDetails(): array
    {
        return $this->extraDetails;
    }
}
