<?php

namespace Tests\Application\Actions\Files;

use App\Application\Actions\Files\UploadAction;
use App\Domain\DomainException\InvalidRequestException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ResponseFactory;

class UploadActionTest extends TestCase
{
    private function createAction(): UploadAction
    {
        $logger = $this->createMock(LoggerInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->with('upload_directory')
            ->willReturn('/tmp/uploads');

        return new UploadAction($logger, $container);
    }

    public function testNoFilesUploaded(): void
    {
        $action = $this->createAction();

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUploadedFiles')->willReturn([]);

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
        $this->expectExceptionMessage('Invalid request: no file found.');

        $actionMethod = $reflection->getMethod('action');
        $actionMethod->setAccessible(true);
        $actionMethod->invoke($action);
    }

    public function testUploadSuccess(): void
    {
        $action = $this->createAction();

        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getClientFilename')->willReturn('test.txt');
        $uploadedFile->method('getSize')->willReturn(1024);
        $uploadedFile->method('getError')->willReturn(UPLOAD_ERR_OK);
        $uploadedFile->method('getClientMediaType')->willReturn('text/plain');
        $uploadedFile->expects($this->once())->method('moveTo');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUploadedFiles')->willReturn([$uploadedFile]);

        $response = (new ResponseFactory())->createResponse();

        $reflection = new \ReflectionClass($action);
        $requestProp = $reflection->getProperty('request');
        $requestProp->setAccessible(true);
        $requestProp->setValue($action, $request);

        $responseProp = $reflection->getProperty('response');
        $responseProp->setAccessible(true);
        $responseProp->setValue($action, $response);

        $actionMethod = $reflection->getMethod('action');
        $actionMethod->setAccessible(true);
        $result = $actionMethod->invoke($action);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertTrue($body['data']['success']);
    }

    /**
     * @dataProvider uploadErrorProvider
     */
    public function testUploadErrors(int $errorCode, string $expectedMessage): void
    {
        $action = $this->createAction();

        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getClientFilename')->willReturn('test.txt');
        $uploadedFile->method('getSize')->willReturn(1024);
        $uploadedFile->method('getError')->willReturn($errorCode);
        $uploadedFile->method('getClientMediaType')->willReturn('text/plain');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUploadedFiles')->willReturn([$uploadedFile]);

        $response = (new ResponseFactory())->createResponse();

        $reflection = new \ReflectionClass($action);
        $requestProp = $reflection->getProperty('request');
        $requestProp->setAccessible(true);
        $requestProp->setValue($action, $request);

        $responseProp = $reflection->getProperty('response');
        $responseProp->setAccessible(true);
        $responseProp->setValue($action, $response);

        $actionMethod = $reflection->getMethod('action');
        $actionMethod->setAccessible(true);
        $result = $actionMethod->invoke($action);

        $body = json_decode((string) $result->getBody(), true);
        $this->assertFalse($body['data']['success']);
        $this->assertEquals($expectedMessage, $body['data']['error_message']);
    }

    public static function uploadErrorProvider(): array
    {
        return [
            'ini size exceeded' => [
                UPLOAD_ERR_INI_SIZE,
                'The uploaded file exceeds the upload_max_filesize directive'
            ],
            'form size exceeded' => [
                UPLOAD_ERR_FORM_SIZE,
                'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form'
            ],
            'partial upload' => [
                UPLOAD_ERR_PARTIAL,
                'The uploaded file was only partially uploaded'
            ],
            'no file' => [
                UPLOAD_ERR_NO_FILE,
                'No file was uploaded'
            ],
            'no tmp dir' => [
                UPLOAD_ERR_NO_TMP_DIR,
                'Missing a temporary folder'
            ],
            'cant write' => [
                UPLOAD_ERR_CANT_WRITE,
                'Failed to write file to disk'
            ],
            'extension stopped' => [
                UPLOAD_ERR_EXTENSION,
                'File upload stopped by extension'
            ],
            'unknown error' => [
                999,
                'Unknown upload error'
            ]
        ];
    }
}
