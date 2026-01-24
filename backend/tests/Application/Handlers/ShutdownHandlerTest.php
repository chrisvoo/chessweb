<?php

declare(strict_types=1);

namespace Tests\Application\Handlers;

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class ShutdownHandlerTest extends TestCase
{
    private function createRequest(): \Psr\Http\Message\ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest('GET', '/test');
    }

    private function createErrorHandler(): HttpErrorHandler
    {
        $callableResolver = $this->createMock(CallableResolverInterface::class);
        $responseFactory = new ResponseFactory();
        $logger = $this->createMock(LoggerInterface::class);

        return new HttpErrorHandler($callableResolver, $responseFactory, $logger);
    }

    public function testShutdownHandlerConstructor(): void
    {
        $request = $this->createRequest();
        $errorHandler = $this->createErrorHandler();

        $handler = new ShutdownHandler($request, $errorHandler, true);

        $this->assertInstanceOf(ShutdownHandler::class, $handler);
    }

    public function testShutdownHandlerWithNoError(): void
    {
        $request = $this->createRequest();
        $errorHandler = $this->createErrorHandler();

        $handler = new ShutdownHandler($request, $errorHandler, false);

        // Clear any previous errors
        error_clear_last();

        // When there's no error, error_get_last() returns null
        // and the handler should not call the error handler or emit anything
        // We just verify it doesn't throw
        $handler();

        $this->assertTrue(true);
    }

    public function testShutdownHandlerWithDisplayErrorDetailsFalse(): void
    {
        $request = $this->createRequest();
        $errorHandler = $this->createErrorHandler();

        $handler = new ShutdownHandler($request, $errorHandler, false);

        $this->assertInstanceOf(ShutdownHandler::class, $handler);
    }

    public function testShutdownHandlerWithDisplayErrorDetailsTrue(): void
    {
        $request = $this->createRequest();
        $errorHandler = $this->createErrorHandler();

        $handler = new ShutdownHandler($request, $errorHandler, true);

        $this->assertInstanceOf(ShutdownHandler::class, $handler);
    }

    /**
     * Test shutdown handler with E_USER_WARNING error and displayErrorDetails = true.
     * This covers the E_USER_WARNING case in the switch statement.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShutdownHandlerWithUserWarning(): void
    {
        $request = $this->createRequest();
        $errorHandler = $this->createErrorHandler();

        $handler = new ShutdownHandler($request, $errorHandler, true);

        // Trigger a user warning to set error_get_last()
        @trigger_error('Test user warning', E_USER_WARNING);

        // Capture and discard output from ResponseEmitter
        ob_start();
        $handler();
        $output = ob_get_clean();

        // Verify the handler processed the error (output should contain JSON response)
        $this->assertNotEmpty($output);
    }

    /**
     * Test shutdown handler with E_USER_NOTICE error and displayErrorDetails = true.
     * This covers the E_USER_NOTICE case in the switch statement.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShutdownHandlerWithUserNotice(): void
    {
        $request = $this->createRequest();
        $errorHandler = $this->createErrorHandler();

        $handler = new ShutdownHandler($request, $errorHandler, true);

        // Trigger a user notice to set error_get_last()
        @trigger_error('Test user notice', E_USER_NOTICE);

        // Capture and discard output from ResponseEmitter
        ob_start();
        $handler();
        $output = ob_get_clean();

        // Verify the handler processed the error
        $this->assertNotEmpty($output);
    }

    /**
     * Test shutdown handler with default error type (not E_USER_ERROR/WARNING/NOTICE).
     * This covers the default case in the switch statement.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShutdownHandlerWithDefaultErrorType(): void
    {
        $request = $this->createRequest();
        $errorHandler = $this->createErrorHandler();

        $handler = new ShutdownHandler($request, $errorHandler, true);

        // Trigger a deprecated error to hit the default case
        @trigger_error('Test deprecated', E_USER_DEPRECATED);

        ob_start();
        $handler();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }

    /**
     * Test shutdown handler with displayErrorDetails = false.
     * This covers the generic message path.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShutdownHandlerWithDisplayErrorDetailsFalseAndError(): void
    {
        $request = $this->createRequest();
        $errorHandler = $this->createErrorHandler();

        // With displayErrorDetails = false, the generic message should be used
        $handler = new ShutdownHandler($request, $errorHandler, false);

        @trigger_error('Test warning', E_USER_WARNING);

        ob_start();
        $handler();
        $output = ob_get_clean();

        // Verify generic message is used (not the detailed one)
        $this->assertStringContainsString('An error while processing your request', $output);
    }

    /**
     * Test that the warning message format is correct.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShutdownHandlerWarningMessageFormat(): void
    {
        $request = $this->createRequest();
        $errorHandler = $this->createErrorHandler();

        $handler = new ShutdownHandler($request, $errorHandler, true);

        @trigger_error('Specific warning message', E_USER_WARNING);

        ob_start();
        $handler();
        $output = ob_get_clean();

        // With displayErrorDetails = true, the message should contain WARNING prefix
        $this->assertStringContainsString('WARNING:', $output);
        $this->assertStringContainsString('Specific warning message', $output);
    }

    /**
     * Test that the notice message format is correct.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShutdownHandlerNoticeMessageFormat(): void
    {
        $request = $this->createRequest();
        $errorHandler = $this->createErrorHandler();

        $handler = new ShutdownHandler($request, $errorHandler, true);

        @trigger_error('Specific notice message', E_USER_NOTICE);

        ob_start();
        $handler();
        $output = ob_get_clean();

        // With displayErrorDetails = true, the message should contain NOTICE prefix
        $this->assertStringContainsString('NOTICE:', $output);
        $this->assertStringContainsString('Specific notice message', $output);
    }
}
