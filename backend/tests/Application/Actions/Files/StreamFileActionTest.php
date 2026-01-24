<?php

namespace Tests\Application\Actions\Files;

use App\Application\Actions\Files\StreamFileAction;
use App\Domain\DomainException\InvalidRequestException;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Request;

class StreamFileActionTest extends TestCase
{
    public function testMissingFileParameter(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $container = $this->createMock(Container::class);

        $action = new StreamFileAction($logger, $streamFactory, $container);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);

        $response = (new ResponseFactory())->createResponse();

        // Use reflection to set protected properties
        $reflection = new \ReflectionClass($action);
        $requestProp = $reflection->getProperty('request');
        $requestProp->setAccessible(true);
        $requestProp->setValue($action, $request);

        $responseProp = $reflection->getProperty('response');
        $responseProp->setAccessible(true);
        $responseProp->setValue($action, $response);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Missing file');

        // Call protected action method
        $actionMethod = $reflection->getMethod('action');
        $actionMethod->setAccessible(true);
        $actionMethod->invoke($action);
    }

    public function testInvalidFilePath(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $container = $this->createMock(Container::class);
        $container->method('get')
            ->with('upload_directory')
            ->willReturn('/var/uploads');

        $action = new StreamFileAction($logger, $streamFactory, $container);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['file' => '../../../etc/passwd']);

        $response = (new ResponseFactory())->createResponse();

        // Use reflection to set protected properties
        $reflection = new \ReflectionClass($action);
        $requestProp = $reflection->getProperty('request');
        $requestProp->setAccessible(true);
        $requestProp->setValue($action, $request);

        $responseProp = $reflection->getProperty('response');
        $responseProp->setAccessible(true);
        $responseProp->setValue($action, $response);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid file path');

        // Call protected action method
        $actionMethod = $reflection->getMethod('action');
        $actionMethod->setAccessible(true);
        $actionMethod->invoke($action);
    }

    public function testNonExistentFile(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $container = $this->createMock(Container::class);
        $container->method('get')
            ->with('upload_directory')
            ->willReturn('/var/uploads');

        $action = new StreamFileAction($logger, $streamFactory, $container);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['file' => 'nonexistent.txt']);

        $response = (new ResponseFactory())->createResponse();

        // Use reflection to set protected properties
        $reflection = new \ReflectionClass($action);
        $requestProp = $reflection->getProperty('request');
        $requestProp->setAccessible(true);
        $requestProp->setValue($action, $request);

        $responseProp = $reflection->getProperty('response');
        $responseProp->setAccessible(true);
        $responseProp->setValue($action, $response);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid file path');

        // Call protected action method
        $actionMethod = $reflection->getMethod('action');
        $actionMethod->setAccessible(true);
        $actionMethod->invoke($action);
    }

    public function testStreamFileSuccess(): void
    {
        // Create a temporary file for testing
        // Use realpath() to get the canonical path (handles symlinks like /tmp -> /private/tmp on macOS)
        $tempDir = sys_get_temp_dir() . '/streamfile_test_' . uniqid();
        mkdir($tempDir, 0777, true);
        $realTempDir = realpath($tempDir);
        $testFile = $realTempDir . '/test.txt';
        file_put_contents($testFile, 'Hello, World!');

        try {
            $logger = $this->createMock(LoggerInterface::class);

            $mockStream = $this->createMock(StreamInterface::class);
            $streamFactory = $this->createMock(StreamFactoryInterface::class);
            $streamFactory->method('createStreamFromFile')
                ->with($testFile)
                ->willReturn($mockStream);

            $container = $this->createMock(Container::class);
            $container->method('get')
                ->with('upload_directory')
                ->willReturn($realTempDir);

            $action = new StreamFileAction($logger, $streamFactory, $container);

            $request = $this->createMock(ServerRequestInterface::class);
            $request->method('getQueryParams')->willReturn(['file' => 'test.txt']);

            $response = (new ResponseFactory())->createResponse();

            // Use reflection to set protected properties
            $reflection = new \ReflectionClass($action);
            $requestProp = $reflection->getProperty('request');
            $requestProp->setAccessible(true);
            $requestProp->setValue($action, $request);

            $responseProp = $reflection->getProperty('response');
            $responseProp->setAccessible(true);
            $responseProp->setValue($action, $response);

            // Call protected action method
            $actionMethod = $reflection->getMethod('action');
            $actionMethod->setAccessible(true);
            $result = $actionMethod->invoke($action);

            $this->assertInstanceOf(ResponseInterface::class, $result);
            $this->assertEquals('text/plain', $result->getHeaderLine('Content-Type'));
            $this->assertSame($mockStream, $result->getBody());
        } finally {
            // Cleanup
            @unlink($testFile);
            @rmdir($realTempDir);
        }
    }
}
