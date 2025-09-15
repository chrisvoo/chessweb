<?php

namespace Scacchilatorre\Management\Commands;

use Scacchilatorre\Management\Services\DumpInterface;
use Scacchilatorre\Management\Services\ImporterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'migrate',
    description: 'Manage the chess club scacchilatorre.it',
    help: <<<TXT
        ./bin/chess migrate -d|--dump: dumps the website from FTP
TXT
)]
class Migrate extends Command
{
    public function __construct(
        private readonly DumpInterface $dumper,
        private readonly ImporterService $importerService,
    ) {
        parent::__construct('migrate');
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(
            description: 'Dumps the website from FTP',
            name: 'dump',
            shortcut: 'd'
        )] bool $dump = false,
        #[Option(
            description: 'Dry run, for debug purposes, it does not insert anything into the database',
            name: 'dry-run',
            shortcut: 'r'
        )] bool $dryRun = false
    ): int
    {
        if ($dump) {
            $io->writeln('- Dumping files from ' . $_ENV['FTP_HOST'] . '...');
            $this
                ->dumper
                ->withIO($io)
                ->dump([
                    DumpInterface::HOST => $_ENV['FTP_HOST'],
                    DumpInterface::USER => $_ENV['FTP_USER'],
                    DumpInterface::PASSWORD => $_ENV['FTP_PASS'],
                    DumpInterface::PORT => $_ENV['FTP_PORT'],
                    DumpInterface::REMOTE_PATH => $_ENV['FTP_REMOTE_PATH'],
                    DumpInterface::LOCAL_PATH => $_ENV['LOCAL_DUMP_PATH'],
                ]);
        }

        $this
            ->importerService
            ->withIO($io)
            ->setDryRun($dryRun)
            ->import(
                $_ENV['LOCAL_DUMP_PATH'] . '/www'
            );

        $io->success('Procedure completed');
        return Command::SUCCESS;
    }
}
