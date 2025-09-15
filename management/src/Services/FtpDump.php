<?php

namespace Scacchilatorre\Management\Services;

use FTP\Connection;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Style\SymfonyStyle;

class FtpDump implements DumpInterface
{
    private bool $dryRun = false;
    private SymfonyStyle $io;

    private function validate(array $options): void
    {
        if (empty($options[self::HOST]) || !is_string($options[self::HOST])) {
            throw new InvalidArgumentException('FTP host cannot be empty and must be string');
        }

        if (empty($options[self::USER]) || !is_string($options[self::HOST])) {
            throw new InvalidArgumentException('FTP user cannot be empty and must be string');
        }

        if (empty($options[self::PASSWORD]) || !is_string($options[self::HOST])) {
            throw new InvalidArgumentException('FTP password cannot be empty and must be string');
        }

        if (empty($options[self::REMOTE_PATH]) || !is_string($options[self::HOST])) {
            throw new InvalidArgumentException('FTP remote path must not be empty and must be string');
        }

        if (empty($options[self::LOCAL_PATH]) || !is_string($options[self::LOCAL_PATH])) {
            throw new InvalidArgumentException('Local path must not be empty and must be string');
        }

        if (!is_dir($options[self::LOCAL_PATH])) {
            throw new InvalidArgumentException("Local path does not exist or is not a directory: {$options[self::LOCAL_PATH]}");
        }

        if (!is_writable($options[self::LOCAL_PATH])) {
            throw new InvalidArgumentException("Local path is not writable: {$options[self::LOCAL_PATH]}");
        }

        if (
            empty($options[self::PORT]) ||
            filter_var($options[self::PORT], FILTER_VALIDATE_INT) === false ||
            !in_array((int)$options[self::PORT], [21, 443])
        ) {
            throw new InvalidArgumentException('FTP port cannot be empty and must be number');
        }
    }

    /**
     * It downloads a website from a source
     * @param array $options Parameters useful for the implementor
     * @return array
     */
    public function dump(array $options = []): array
    {
        $this->validate($options);
        $this->io->info('FTP download started');

        try {
            $ftp = ftp_connect($options[self::HOST], $options[self::PORT]);

            if (!$ftp) {
                $this->io->error('Could not connect to ftp server');
                return [];
            }

            $login_result = @ftp_login($ftp, $options[self::USER], $options[self::PASSWORD]);

            if (!$login_result) {
                $this->io->error('Invalid FTP credentials!');
                return [];
            }

            $this->io->writeln('- Connection established');

            // Enable passive mode (usually necessary for firewalls)
            ftp_pasv($ftp, true);

            $this->cleanLocalPath($options[self::LOCAL_PATH]);
            $this->download($ftp, $options[self::REMOTE_PATH], $options[self::LOCAL_PATH]);

            $this->io->info('- Done, closing connection');
            ftp_close($ftp);
        } catch (\Exception $e) {
            $this->io->error($e->getMessage() . ' at line ' . $e->getLine());
        } finally {
            ftp_close($ftp);
        }

        return [];
    }

    private function download(Connection $ftp, string $remoteDir, string $localDir): void
    {
        // Get list of items in the current remote directory
        $contents = ftp_nlist($ftp, ".");
        if ($contents === false) {
            return; // Could not list directory contents
        }

        foreach ($contents as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $localItemPath = $localDir . DIRECTORY_SEPARATOR . $item;
            // A common trick: ftp_size returns -1 for directories
            if (ftp_size($ftp, $item) === -1) {
                // This is a directory
                if (!is_dir($localItemPath)) {
                    mkdir($localItemPath);
                }

                // Go into the subdirectory and recurse
                ftp_chdir($ftp, $item);
                $this->download($ftp, $remoteDir . '/' . $item, $localItemPath);
                ftp_chdir($ftp, '..'); // Go back up to the parent directory
            } else {
                // This is a file, download it
                if (!ftp_get($ftp, $localItemPath, $item, FTP_BINARY)) {
                    $this->io->writeln("- <fg=yellow>Failed to download file: {$item}</>");
                }
            }
        }
    }

    private function cleanLocalPath(string $localPath): void
    {
        $this->io->writeln('- Cleaning local files from ' . $localPath);

        // Use a recursive iterator to find all files and directories
        // CHILD_FIRST ensures we delete files inside a directory before the directory itself
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($localPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            // Do not delete the .gitignore file
            if ($fileinfo->getFilename() === '.gitignore') {
                continue;
            }

            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }
    }

    public function withIO(SymfonyStyle $io): ServiceInterface
    {
        $this->io = $io;
        return $this;
    }

    public function setDryRun(bool $dryRun): ServiceInterface
    {
        $this->dryRun = $dryRun;
        return $this;
    }
}
