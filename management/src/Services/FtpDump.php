<?php

namespace Scacchilatorre\Management\Services;

use InvalidArgumentException;
use Symfony\Component\Console\Style\SymfonyStyle;

class FtpDump implements DumpInterface
{
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

        try {
            $ftp = ftp_connect($options[self::HOST], $options[self::PORT]);

            if (!$ftp) {
                $this->io->error('Could not connect to ftp server');
                return [];
            }

            $login_result = ftp_login($ftp, $options[self::USER], $options[self::PASSWORD]);

            if (!$login_result) {
                $this->io->error('Invalid FTP credentials!');
                return [];
            }

            $this->cleanLocalPath($options[self::LOCAL_PATH]);

            $this->io->writeln('Closing connection');
            ftp_close($ftp);
        } catch (\Exception $e) {

        }

        return [];
    }

    private function cleanLocalPath(string $localPath): void
    {
        $this->io->writeln('Cleaning local files from ' . $localPath);
    }

    public function withIO(SymfonyStyle $io): IOInterface
    {
        $this->io = $io;
        return $this;
    }
}
