<?php

namespace Scacchilatorre\Management\Services;

interface DumpInterface extends Service
{
    public const HOST = 'host';
    public const USER = 'user';
    public const PASSWORD = 'password';
    public const PORT = 'port';
    public const REMOTE_PATH = 'remote_path';
    public const LOCAL_PATH = 'local_path';

    /**
     * It downloads a website from a source
     * @param array $options Parameters useful for the implementor
     * @return array
     */
    public function dump(array $options = []): array;
}
