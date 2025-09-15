<?php

namespace Scacchilatorre\Management\Commands;

use Scacchilatorre\Management\Services\Crawler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'collect',
    description: 'Extract public paths from images and links of the pages',
    help: <<<TXT
        ./bin/chess collect: provides a list of public paths and related files
TXT
)]
class Collect extends Command
{
    public function __construct(
        private readonly Crawler $crawler,
    ) {
        parent::__construct('collect');
    }

    public function __invoke(
        SymfonyStyle $io
    ): int
    {
        $io->title('Collecting public paths');
        $result = $this->crawler
                    ->withIO($io)
                    ->crawl(
                        $_ENV['LOCAL_DUMP_PATH'] . DIRECTORY_SEPARATOR . 'www',
                        ["/pagine_e_file_vecchi"]
                    );

        $io->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }
}
