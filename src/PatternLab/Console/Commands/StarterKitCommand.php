<?php

/*!
 * Console StarterKit Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Fetch;
use \PatternLab\Timer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StarterKitCommand extends Command {
	
	protected function configure() {
		
		$this
			->setName('starterkit')
			->setDescription('Initialize or fetch a specific StarterKit')
			->setHelp('The StarterKit command downloads StarterKits.')
			->addOption('init', 'i', InputOption::VALUE_NONE, 'Initialize with a blank StarterKit based on the active PatternEngine.')
			->addOption('install', 'f', InputOption::VALUE_REQUIRED, 'Fetch a specific StarterKit from GitHub.')
		;
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		// find the value given to the command
		$init       = $input->getOption('init');
		$starterkit = $input->getOption('install');
		
		if ($init) {
			$patternEngine = Config::getOption("patternExtension");
			$starterkit    = "pattern-lab/starterkit-".$patternEngine."-base";
		}
		
		if ($starterkit) {
			
			// download the starterkit
			$f = new Fetch();
			$f->fetchStarterKit($starterkit);
			
		}
		
	}
	
}
