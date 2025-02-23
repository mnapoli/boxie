<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'install')]
class Install extends Command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Install a package')
            ->addArgument('package', InputArgument::REQUIRED, 'The package to install')
            ->addArgument('version', InputArgument::OPTIONAL, 'The version to install', 'latest')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem;

        $package = $input->getArgument('package');
        $packageDirectory = dirname(__DIR__, 2) . "/packages/$package";
        if (!$fs->exists($packageDirectory)) {
            $output->writeln("Package $package not found");
            return 1;
        }
        $version = $input->getArgument('version');

        if (! $fs->exists("$packageDirectory/info.json")) {
            $output->writeln("Package $package is broken, missing info.json");
            return 1;
        }
        $packageInfo = json_decode(file_get_contents("$packageDirectory/info.json"), true, 512, JSON_THROW_ON_ERROR);
        $binaries = $packageInfo['bin'] ?? [];

        foreach ($binaries as $binary) {
            $this->installBinary($package, $version, $binary, $packageDirectory, $fs, $output);
        }

        return 0;
    }

    private function installBinary(string $package, string $version, string $binary, string $packageDirectory, Filesystem $fs, OutputInterface $output): void
    {
        // If there is a `$binary.install.sh` script, run it
        $installScript = "$packageDirectory/$binary.install.sh";
        if ($fs->exists($installScript)) {
            $output->writeln("Running installation script for $package/$binary");
            $installProcess = new Process(
                ["sh", $installScript],
                cwd: $packageDirectory,
                env: ['VERSION' => $version],
                timeout: 600, // 10 minutes
            );
            // Stream the output
            $installProcess->start();
            $installProcess->wait(fn($type, $buffer) => $output->write($buffer));
            if ($installProcess->getExitCode() !== 0) {
                $output->writeln("Failed to build image for $package/$binary");
                return;
            }
        }

        if (!$fs->exists("$packageDirectory/$binary")) {
            $output->writeln("Package $package/$binary is broken, missing binary");
            return;
        }
        $binaryFile = __DIR__ . "/../../bin/$binary";
        if ($version !== 'latest') {
            $binaryFile .= "@$version";
        }

        $script = <<<EOF
#!/usr/bin/env sh
# This file is generated, do not edit

__DIRNAME=$(dirname $(readlink -f "$0"))
export VERSION="$version"

\$__DIRNAME/../packages/$package/$binary "\$@"
EOF;

        $fs->dumpFile($binaryFile, $script);

        // Add execute permissions
        $fs->chmod($binaryFile, 0755);

        $output->writeln("Installed $binary@$version");
    }
}
