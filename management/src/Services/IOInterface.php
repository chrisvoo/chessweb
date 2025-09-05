<?php

namespace Scacchilatorre\Management\Services;

use Symfony\Component\Console\Style\SymfonyStyle;

interface IOInterface
{
    public function withIO(SymfonyStyle $io): self;
}
