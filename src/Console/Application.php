<?php

namespace StellarWP\Strauss\Console;

use BrianHenryIE\Strauss\Console\Commands\Compose;
use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
	/**
	 * @param string $version
	 */
	public function __construct(string $version)
	{
		parent::__construct('stellar-strauss', $version);

		$composeCommand = new Commands\Builder();
		$this->add($composeCommand);

		$this->setDefaultCommand('build');
	}
}
