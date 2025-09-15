<?php

namespace Scacchilatorre\Management\Services;

use Symfony\Component\Console\Style\SymfonyStyle;

interface ServiceInterface
{
    public function withIO(SymfonyStyle $io): self;
    public function setDryRun(bool $dryRun): self;
}
