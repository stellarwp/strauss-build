<?php

namespace StellarWP\Strauss\Console;

use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
	/**
	 * @param string $version
	 */
	public function __construct(string $version)
	{
		parent::__construct('stellar-strauss', $version);

		$composeCommand = new Commands\Build();
		$this->add($composeCommand);

		$this->setDefaultCommand('build');
	}
}
