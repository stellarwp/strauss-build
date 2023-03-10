<?php

namespace StellarWP\Strauss\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class Build extends Command
{
	private $command;
	private $io;
	private $straussPath = './bin/strauss.phar';
	private $straussVersion = '0.13.0';
	private $scripts = [];
	private $targetDirectory = 'vendor-prefixed';

	protected function configure() {
		$this->setName( 'build' );
		$this->setDescription( 'Runs Strauss in a fault-tolerant way.' );
		$this->setHelp( '' );
	}

	public function getDefinition()
	{
		return [
			['command', null, InputOption::VALUE_OPTIONAL, 'Composer command to run', 'install'],
		];
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$this->command = $input->getOption('command') ?? 'install';
		var_dump( $this->command );
		die;

		$composerJson = json_decode(file_get_contents('composer.json'), true);
		if (isset($composerJson['extra']['strauss']['target_directory'])) {
			$this->targetDirectory = $composerJson['extra']['strauss']['target_directory'];
		}

		if (isset($composerJson['extra']['stellar-strauss']['strauss-version'])) {
			$this->straussVersion = $composerJson['extra']['stellar-strauss']['strauss-version'];
		}

		if (isset($composerJson['extra']['stellar-strauss']['strauss-path'])) {
			$this->straussPath = $composerJson['extra']['stellar-strauss']['strauss-path'];
		}

		if (isset($composerJson['extra']['stellar-strauss']['scripts'])) {
			$this->scripts = $composerJson['extra']['stellar-strauss']['scripts'];
		}

		if (file_exists('.strauss-rebuild')) {
			// Run Strauss
			$output->writeln('Running Strauss...');
			$this->runStrauss();

			// Remove .strauss-rebuild
			unlink('.strauss-rebuild');
		} else {
			// Prep directories for Strauss build
			$output->writeln('Prepping directories for a Strauss build...');
			if (!file_exists($this->straussPath)) {
				passthru("curl -o {$this->straussPath} -L -C - https://github.com/BrianHenryIE/strauss/releases/download/{$this->straussVersion}/strauss.phar");
			}

			// Find all namespaced packages in the strauss directory
			$output->writeln('Finding all namespaced packages in the Strauss directory...');
			$packages = $this->findNamespacedPackages();
			file_put_contents('.strauss-rebuild', implode("\n", $packages));

			// Remove the target directory
			$output->writeln('Removing target directory...');
			$this->removeTargetDirectory();

			// Remove all strauss-enabled packages from the vendor directory
			$output->writeln('Removing all Strauss-enabled packages from the vendor directory...');
			$this->removeStraussEnabledPackages($packages);

			// Run Composer
			$output->writeln('Running Composer...');
			$composerCommand = ($input->hasParameterOption('--no-dev')) ? $this->command.' --no-dev' : $this->command;
			passthru('composer '.$composerCommand);

			// Run post-command scripts
			$output->writeln('Running post-command scripts...');
			$this->runPostCommandScripts();
		}
	}

	private function findNamespacedPackages()
	{
		$namespacedPackages = [];

		$finder = new Finder();
		$finder
			->directories()
			->depth('== 2')
			->in($this->targetDirectory)
			->sortByName();

		foreach ($finder as $dir) {
			$package = str_replace('/', '\\', $dir->getRelativePathname());
			$namespacedPackages[] = $package;
		}

		return $namespacedPackages;
	}

	private function runStrauss()
	{
		passthru("php {$this->straussPath}");

		if (file_exists('vendor/lucatume/di52')) {
			file_put_contents('vendor/lucatume/di52/aliases.php', '<?php');
			file_put_contents("{$this->targetDirectory}/lucatume/di52/aliases.php", '<?php');
		}
	}

	private function runPostCommandScripts()
	{
		foreach ($this->scripts as $script) {
			$this->io->write(sprintf("Executing %s...\n", $script));
			passthru($script);
		}
	}

	private function removeStraussEnabledPackages($packages)
	{
		foreach ($packages as $package) {
			$dir = "vendor/{$package}";
			if (is_dir($dir)) {
				$this->io->write(sprintf("Removing directory %s...\n", $dir));
				$this->removeDirectory($dir);
			}
		}
	}

	private function removeTargetDirectory()
	{
		$dir = rtrim($this->targetDirectory, '/') . '/';
		if (is_dir($dir)) {
			$this->io->write(sprintf("Removing directory %s...\n", $dir));
			$this->removeDirectory($dir);
		}
	}

}
