<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

abstract class Action
{
    protected LoggerInterface $logger;

    protected Request $request;

    protected Response $response;

    protected array $args;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        try {
            return $this->action();
        } catch (DomainRecordNotFoundException $e) {
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }
    }

    /**
     * @throws DomainRecordNotFoundException
     * @throws HttpBadRequestException
     */
    abstract protected function action(): Response;

    /**
     * @return array|object
     */
    protected function getFormData()
    {
        return $this->request->getParsedBody();
    }

    /**
     * @param string $name
     * @return mixed
     */
    protected function resolveArg(string $name): mixed
    {
        if (!isset($this->args[$name])) {
            throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
        }

        return $this->args[$name];
    }

    /**
     * Emits a response with data, status and headers specified. Content-Type will be automatically set
     * to application/json if not specified.
     * @param mixed $data The data to be returned
     * @param int $statusCode The response status code
     * @param array $headers Optional headers to be sent with the response
     * @return Response
     */
    protected function respondWithData(mixed $data = null, int $statusCode = 200, array $headers = []): Response
    {
        $payload = new ActionPayload($statusCode, $data);

        return $this->respond($payload, $headers);
    }

    protected function respond(ActionPayload $payload, array $headers = []): Response
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($json);
        $this->response = $this->response->withStatus($payload->getStatusCode());

        if (empty($headers) || !array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = 'application/json';
        }

        foreach ($headers as $key => $value) {
            $this->logger->debug($key, [$value]);
            // destructive method, need to reassign the response for avoid losing the headers
            $this->response = $this->response->withHeader($key, $value);
        }

        return $this->response;
    }
}
