<?php

namespace Scacchilatorre\Management\Commands;

use Symfony\Component\Console\Attribute\Argument;
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
        ./bin/chess deploy -e remote --dry-run: will print the commands for deploying remotely the app
        ./bin/chess deploy -e remote frontend: will just deploy remotely the frontend
TXT
)]
class Deploy extends Command
{
    private SymfonyStyle $io;
    private string $environment;
    private array $availableDeployTypes = ['frontend', 'backend', 'both'];
    private bool $dryRun = false;

    private function getTitle(string $type): string
    {
        $title = 'Deploying ';
        $title .= $type === 'both' ? 'both frontend and backend' : $type;
        $title .= " to " . $this->environment;
        $title .= $this->dryRun ? ' (dry-run)' : '';

        return $title;
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(
            description: 'Specify the target environment (default: local)',
            name: 'environment',
            shortcut: 'e',
            suggestedValues: ['local', 'remote']
        )] string $environment = 'local',
        #[Argument(
            description: 'Deploys one or more specific parts (default: both)',
            name: 'type',
            suggestedValues: ['frontend', 'backend', 'both']
        )] string $type = 'both',
        #[Option(
            description: 'Dry run (default: false)',
            name: 'dry-run'
        )] bool $dryRun = false,
    ): int
    {
        $this->io = $io;
        $this->environment = $environment;
        $this->dryRun = $dryRun;

        if (!in_array($type, $this->availableDeployTypes)) {
            $io->error("Invalid Deploy type '$type'");
            return Command::FAILURE;
        }

        $io->title($this->getTitle($type));

        $rootDir = getcwd();

        if (
            in_array($type, ['both', 'backend']) &&
            $this->deployBackend($rootDir) !== Command::SUCCESS
        ) {
           return Command::FAILURE;
        }

        if (
            in_array($type, ['both', 'frontend']) &&
            $this->deployFrontend($rootDir) !== Command::SUCCESS
        ) {
            return Command::FAILURE;
        }

        $io->success('Deploying complete');
        return Command::SUCCESS;
    }

    private function deployLocalBackend(): int
    {
        $this->io->writeln('Copying backend files to local server...');
        $output = [];
        $retval = null;

        $command = 'cp -R . ' . $_ENV['LOCAL_DEPLOY_PATH'] . ' 2>&1';
        if ($this->dryRun) {
            $this->io->writeln($command);
            return Command::SUCCESS;
        }

        exec($command, $output, $retval);
        if ($retval !== Command::SUCCESS) {
            $this->io->error('Failed to copy application files: ' . join("\n", $output));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function deployRemoteBackend(): int
    {
        $this->io->writeln('Copying backend files to remote server...');

        // Rsync arguments explained:
        // -a: Archive mode (recurse, preserve permissions, times, etc.)
        // -v: Verbose output
        // -z: Compress file data during the transfer
        // -e: Specify the remote shell (needed to set the custom SSH port)
        // --exclude: Prevent uploading unnecessary/sensitive files
        $excludeList = [
            ".git",
            ".github",
            "data",
            "*.xml",
            "*.dist",
            "*.bak",
            ".phpunit.result.cache",
            ".env",
            "tests",
            "vendor",
            "*.md",
            ".coveralls.yml"
        ];

        $excludeParams = array_map(function ($item) {
            return '--exclude=' . escapeshellarg($item);
        }, $excludeList);
        $excludeString = implode(' ', $excludeParams);

        $command = sprintf(
            'rsync -avz -e "ssh -p %s" %s . %s@%s:%s 2>&1',
            $_ENV['FTP_PORT'],
            $excludeString,
            $_ENV['FTP_USER'],
            $_ENV['FTP_HOST'],
            $_ENV['FTP_REMOTE_PATH']
        );

        if ($this->dryRun) {
            $this->io->writeln($command);
        } else {
            $output = [];
            $retval = null;
            exec($command, $output, $retval);

            if ($retval !== Command::SUCCESS) {
                $this->io->error('Rsync failed for backend:');
                $this->io->writeln($output);
                return Command::FAILURE;
            }

            $this->io->success('Backend files copied successfully.');
        }


        // 2. Install Dependencies Remotely
        $this->io->writeln('Running "composer install" on remote server...');

        // We ssh into the server, cd to the directory, and run composer.
        // --no-dev: Don't install development dependencies (phpunit, etc) on prod
        // --optimize-autoloader: Speeds up class loading
        $composerCommand = sprintf(
            'ssh -p %s %s@%s "cd %s && composer install --no-dev --optimize-autoloader --no-interaction 2>&1"',
            $_ENV['FTP_PORT'],
            $_ENV['FTP_USER'],
            $_ENV['FTP_HOST'],
            $_ENV['FTP_REMOTE_PATH']
        );

        $output = [];
        $retval = null;
        if ($this->dryRun) {
            $this->io->writeln($composerCommand);
        } else {
            exec($composerCommand, $output, $retval);

            if ($retval !== Command::SUCCESS) {
                $this->io->error('Remote composer install failed:');
                $this->io->writeln($output);
                return Command::FAILURE;
            }

            $this->io->success('Composer dependencies installed successfully.');
        }

        return Command::SUCCESS;
    }

    private function deployBackend(string $rootDir): int
    {
        $this->io->section('Backend');
        chdir($rootDir . DIRECTORY_SEPARATOR . '../backend');
        $this->io->writeln('Current directory: ' . getcwd());

        if ($this->environment === 'local') {
            return $this->deployLocalBackend();
        }

        return $this->deployRemoteBackend();
    }

    private function deployLocalFrontend(): int
    {
        $this->io->writeln('Copying backend files to local server...');
        $output = [];
        $retval = null;

        $command = 'cp -R dist/browser/* ' . $_ENV['LOCAL_DEPLOY_PATH'] . DIRECTORY_SEPARATOR . 'public 2>&1';
        if ($this->dryRun) {
            $this->io->writeln($command);
            return Command::SUCCESS;
        }

        exec($command,$output, $retval);
        if ($retval === Command::SUCCESS) {
            $this->io->writeln('Frontend files successfully copied');
        } else {
            $this->io->error('Frontend files copy failed [' . $retval . ']');
            $this->io->writeln(join("\n", $output));
        }

        return $retval;
    }

    private function deployRemoteFrontend(): int
    {
        $this->io->writeln('Copying backend files to remote server...');

        $output = [];
        $retval = null;

        // Ensure we target the 'public' folder on the remote,
        // similar to how the local deploy targets LOCAL_DEPLOY_PATH/public
        $remotePublicPath = rtrim($_ENV['FTP_REMOTE_PATH'], '/') . '/public';

        // Note on the source path "dist/browser/":
        // In rsync, the trailing slash is CRITICAL.
        // "dist/browser/" means "copy the CONTENTS of this folder".
        // "dist/browser" (no slash) means "copy the folder ITSELF".
        // We want the contents to go into the remote 'public' folder.
        $command = sprintf(
            'rsync -avz -e "ssh -p %s" dist/browser/ %s@%s:%s 2>&1',
            $_ENV['FTP_PORT'],
            $_ENV['FTP_USER'],
            $_ENV['FTP_HOST'],
            $remotePublicPath
        );

        if ($this->dryRun) {
            $this->io->writeln($command);
            return Command::SUCCESS;
        }
        exec($command, $output, $retval);

        if ($retval !== Command::SUCCESS) {
            $this->io->error('Rsync failed for frontend:');
            $this->io->writeln($output);
            return Command::FAILURE;
        }

        $this->io->success('Frontend files copied successfully.');
        return Command::SUCCESS;
    }

    private function deployFrontend(string $rootDir): int
    {
        $this->io->section('Frontend');
        chdir($rootDir . DIRECTORY_SEPARATOR . '../frontend');
        $this->io->writeln('Current directory: ' . getcwd());

        $command = 'ng build --optimization --delete-output-path 2>&1';
        if ($this->dryRun) {
            $this->io->writeln($command);
        } else {
            $output = [];
            $retval = null;
            exec($command, $output, $retval);
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
        }

        if ($this->environment === 'local') {
            return $this->deployLocalFrontend($rootDir);
        }

        return $this->deployRemoteFrontend($rootDir);
    }
}
