<?php

namespace StellarWP\Strauss\Console\Commands;

use Composer\Command\Command;
use Composer\Plugin\PluginInterface;

class Build implements PluginInterface, Command
{
	private $io;
	private $straussPath = './bin/strauss.phar';
	private $straussVersion = '0.13.0';
	private $scripts = [];

	public function activate(Composer $composer, IOInterface $io)
	{
		$this->io = $io;
	}

	public function getDefinition()
	{
		return [
			['command', null, InputOption::VALUE_OPTIONAL, 'Composer command to run', 'install'],
			['strauss', null, InputOption::VALUE_OPTIONAL, 'Path to Strauss binary', './bin/strauss.phar'],
			['strauss-version', null, InputOption::VALUE_OPTIONAL, 'Version of Strauss to use', '0.13.0'],
			['script', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Script to run after the command'],
		];
	}

	public function configure(InputInterface $input, OutputInterface $output)
	{
		$this->straussPath = $input->getOption('strauss');
		$this->straussVersion = $input->getOption('strauss-version');
		$this->scripts = $input->getOption('script');
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$command = $input->getOption('command');
		$positional_args = $input->getArgument('args');

		// Get target directory
		$targetDirectory = 'vendor-prefixed';
		$composerJson = json_decode(file_get_contents('composer.json'), true);
		if (isset($composerJson['extra']['strauss']['target_directory'])) {
			$targetDirectory = $composerJson['extra']['strauss']['target_directory'];
		}

		if (file_exists('.strauss-rebuild')) {
			// Run Strauss
			$output->writeln('Running Strauss...');
			$this->runStrauss($targetDirectory);

			// Remove .strauss-rebuild
			unlink('.strauss-rebuild');
		} else {
			// Prep directories for Strauss build
			$output->writeln('Prepping directories for a Strauss build...');

			// Find all namespaced packages in the strauss directory
			$output->writeln('Finding all namespaced packages in the Strauss directory...');
			$packages = $this->findNamespacedPackages($targetDirectory);

			// Remove the target directory
			$output->writeln('Removing target directory...');
			$this->removeTargetDirectory($targetDirectory);

			// Remove all strauss-enabled packages from the vendor directory
			$output->writeln('Removing all Strauss-enabled packages from the vendor directory...');
			$this->removeStraussEnabledPackages($packages);

			// Run Composer
			$output->writeln('Running Composer...');
			$composerCommand = ($input->hasParameterOption('--no-dev')) ? $command.' --no-dev' : $command;
			passthru('composer '.$composerCommand);

			// Run post-command scripts
			$output->writeln('Running post-command scripts...');
			$this->runPostCommandScripts($positional_args);
		}
	}

	private function runStrauss($targetDirectory)
	{
		passthru("php {$this->straussPath}");

		if (file_exists('vendor/lucatume/di52')) {
			file_put_contents('vendor/lucatume/di52/aliases.php', '<?php');
			file_put_contents("$targetDirectory/lucatume/di52/aliases.php", '<?php');
		}
	}
}
