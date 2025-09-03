<?php

namespace Scacchilatorre\Management\Commands;

interface DumpInterface
{
    /**
     * It downloads a website from a source
     * @param array $options Parameters useful for the implementor
     * @return array
     */
    public function dump(array $options = []): array;
}
