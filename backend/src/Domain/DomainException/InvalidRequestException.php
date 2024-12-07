<?php

namespace App\Domain\DomainException;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

class InvalidRequestException extends HttpBadRequestException
{
    public function __construct(ServerRequestInterface $request, string $message)
    {
        parent::__construct($request, $message);
    }
}
