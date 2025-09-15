<?php

namespace Scacchilatorre\Management\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'deploy',
    description: 'Deploy the files to the specified server',
    help: <<<TXT
        ./bin/chess deploy -e local: deploy the files to the local server (this is the default)
        ./bin/chess deploy -e remote: deploy the files to the remote server
TXT
)]
class Deploy extends Command
{
    private SymfonyStyle $io;
    private string $environment;

    public function __invoke(
        SymfonyStyle $io,
        #[Option(
            description: 'Specify the target environment (default: local)',
            name: 'environment',
            shortcut: 'e',
            suggestedValues: ['local', 'remote']
        )] string $environment = 'local',
        #[Option(
            description: 'Just deploys the frontend (default: false)',
            name: 'frontend',
            shortcut: 'f',
        )] bool $onlyFrontend = false,
        #[Option(
            description: 'Just deploys the backend (default: false)',
            name: 'backend',
            shortcut: 'b',
        )] bool $onlyBackend = false,
    ): int
    {
        $this->io = $io;
        $this->environment = $environment;
        $io->title('Deploying to ' . $environment);

        $rootDir = getcwd();
        if ($this->deployBackend($rootDir) !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        if ($this->deployFrontend($rootDir) !== Command::SUCCESS) {
            return Command::FAILURE;
        }

        $io->success('Deploying complete');
        return Command::SUCCESS;
    }

    private function deployBackend(string $rootDir): int
    {
        chdir($rootDir . DIRECTORY_SEPARATOR . '../backend');
        $this->io->writeln('Current directory: ' . getcwd());

        $output = [];
        $retval = null;

        if ($this->environment === 'local') {
            $this->io->writeln('Copying application files...');
            exec('cp -R . ' . $_ENV['LOCAL_DEPLOY_PATH'] . ' 2>&1', $output, $retval);
            if ($retval !== Command::SUCCESS) {
                $this->io->error('Failed to copy application files: ' . $output);
                return Command::FAILURE;
            }
        } else {
            // @TODO
        }

        return Command::SUCCESS;
    }

    private function deployFrontend(string $rootDir): int
    {
        chdir($rootDir . DIRECTORY_SEPARATOR . '../frontend');
        $this->io->writeln('Current directory: ' . getcwd());

        $output = [];
        $retval = null;
        exec('ng build --optimization --delete-output-path 2>&1', $output, $retval);
        if ($retval === Command::SUCCESS) {
            $this->io->writeln('Frontend successfully built');
            foreach ($output as $line) {
                if (
                    str_contains(strtolower($line), 'warning') ||
                    str_contains(strtolower($line), 'error')
                ) {
                    $this->io->writeln($line);
                }
            }
        } else {
            $this->io->error('Frontend build failed [' . $retval . ']');
            $this->io->writeln(join("\n", $output));
        }

        if ($this->environment === 'local') {
            exec('cp -R dist/browser/* ' . $_ENV['LOCAL_DEPLOY_PATH'] . DIRECTORY_SEPARATOR . 'public 2>&1', $output, $retval);
            if ($retval === Command::SUCCESS) {
                $this->io->writeln('Frontend files successfully copied');
            }
        } else {
            $this->io->error('Frontend files copy failed [' . $retval . ']');
            $this->io->writeln(join("\n", $output));
        }

        return $retval;
    }
}
