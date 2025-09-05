<?php

namespace Scacchilatorre\Management\Commands;

use Dotenv\Dotenv;
use Scacchilatorre\Management\Services\DumpInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        private readonly DumpInterface $dumper
    ) {
        parent::__construct('migrate');
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(
            description: 'Dumps the website from FTP',
            name: 'dump',
            shortcut: 'd'
        )] bool $dump = false//, #[Argument] string $name, #[Option] bool $activate = false,
    ): int
    {
        Dotenv::createImmutable(__DIR__ . '/../../')->load();

        if ($dump) {
            $io->writeln('Dumping files from ' . $_ENV['FTP_HOST'] . '...');
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

//        $term1 = rand(1, 10);
//        $term2 = rand(1, 10);
//        $result = $term1 + $term2;
//
//        $answer = (int) $io->ask(sprintf('What is %s + %s?', $term1, $term2));
//
//        if ($answer === $result) {
//            $io->success('Well done!');
//        } else {
//            $io->error(sprintf('Aww, so close. The answer was %s', $result));
//        }

        return Command::SUCCESS;
    }
}
