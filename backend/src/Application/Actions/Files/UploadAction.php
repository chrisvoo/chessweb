<?php

namespace App\Application\Actions\Files;

use App\Application\Actions\Action;
use App\Domain\DomainException\InvalidRequestException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;

class UploadAction extends Action
{
    public function __construct(
        protected LoggerInterface $logger,
        private readonly ContainerInterface $container
    ) {
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $directory = $this->container->get('upload_directory');
        /** @var UploadedFileInterface[] $files */
        $files = $this->request->getUploadedFiles();
        $errMessage = '';

        if (count($files) === 0) {
            throw new InvalidRequestException(
                $this->request,
                'Invalid request: no file found.'
            );
        }

        foreach ($files as $file) {
            $this->logger->info(
                json_encode([
                    'file_name' => $file->getClientFilename(),
                    'size' => $file->getSize(),
                    'error' => $file->getError(),
                    'media_type' => $file->getClientMediaType(),
                ])
            );

            switch ($file->getError()) {
                case UPLOAD_ERR_OK:
                    $file->moveTo($directory . DIRECTORY_SEPARATOR . $file->getClientFilename());
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    $errMessage = 'The uploaded file exceeds the upload_max_filesize directive';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errMessage = 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errMessage = 'The uploaded file was only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errMessage = 'No file was uploaded';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errMessage = 'Missing a temporary folder';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errMessage = 'Failed to write file to disk';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errMessage = 'File upload stopped by extension';
                    break;
                default:
                    $errMessage = 'Unknown upload error';
            }
        }

        $data = [
            'success' => empty($errMessage)
        ];

        if (!$data['success']) {
            $data['error_message'] = $errMessage;
        }

        return $this->respondWithData($data);
    }
}
