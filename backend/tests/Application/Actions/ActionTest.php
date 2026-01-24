<?php

declare(strict_types=1);

namespace Tests\Application\Actions;

use App\Application\Actions\Action;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Concrete implementation of Action for testing purposes
 */
class TestableAction extends Action
{
    private string $argToResolve;

    public function __construct(LoggerInterface $logger, string $argToResolve = 'id')
    {
        parent::__construct($logger);
        $this->argToResolve = $argToResolve;
    }

    protected function action(): Response
    {
        // This will call resolveArg which may throw if arg is missing
        $resolvedValue = $this->resolveArg($this->argToResolve);
        return $this->respondWithData(['resolved' => $resolvedValue]);
    }
}

class ActionTest extends TestCase
{
    private function createAction(string $argToResolve = 'id'): TestableAction
    {
        $logger = $this->createMock(LoggerInterface::class);
        return new TestableAction($logger, $argToResolve);
    }

    public function testResolveArgSuccess(): void
    {
        $action = $this->createAction('id');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
        $response = (new ResponseFactory())->createResponse();

        $result = $action($request, $response, ['id' => 123]);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertEquals(123, $body['data']['resolved']);
    }

    public function testResolveArgThrowsHttpBadRequestExceptionWhenArgMissing(): void
    {
        $action = $this->createAction('missing_arg');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
        $response = (new ResponseFactory())->createResponse();

        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage('Could not resolve argument `missing_arg`.');

        $action($request, $response, ['id' => 123]);
    }

    public function testResolveArgThrowsHttpBadRequestExceptionWithEmptyArgs(): void
    {
        $action = $this->createAction('id');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/test');
        $response = (new ResponseFactory())->createResponse();

        $this->expectException(HttpBadRequestException::class);
        $this->expectExceptionMessage('Could not resolve argument `id`.');

        $action($request, $response, []);
    }
}
