<?php

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class LogoutAction extends Action
{

    /**
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $domain = str_replace(['http://', 'https://'], '', $_ENV['JWT_ISSUER']);
        $secure = boolval($_ENV['PRODUCTION']) === true;

        setcookie(
            $_ENV['COOKIE_RT_NAME'],
            '',
            time() - 3600,           // set to past time, 3600 is just a convention
            '/',
            $domain,
            $secure,
            true
        );

        return $this->respondWithData(['message' => 'Logged out']);
    }
}
