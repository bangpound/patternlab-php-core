<?php

/*!
 * Console Version Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Console\Command;
use \PatternLab\Timer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends Command {
	
	protected function configure() {
		
		$this->setName('version')
			->setDescription('Print the version number')
			->setHelp('The version command prints out the current version of Pattern Lab.')
		;
	}
	
	public function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("<info>you're running <desc>v".Config::getOption("v")."</desc> of the PHP version of Pattern Lab...</info>");
	}
	
}
