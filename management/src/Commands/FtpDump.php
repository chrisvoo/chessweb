<?php

namespace Scacchilatorre\Management\Commands;

class FtpDump implements DumpInterface
{
    public const HOST = 'host';
    public const USER = 'user';
    public const PASSWORD = 'password';
    public const PORT = 'port';
    public const REMOTE_PATH = 'remote_path';

    /**
     * It downloads a website from a source
     * @param array $options Parameters useful for the implementor
     * @return array
     */
    public function dump(array $options = []): array
    {
        // TODO: Implement dump() method.
    }
}
