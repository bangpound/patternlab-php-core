<?php

/*!
 * Console Fetch Command Class
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

class FetchCommand extends Command {
	
	protected function configure()
	{
		
		$this->setName('fetch')
			->setDescription('Fetch a package or StarterKit')
			->setHelp('The fetch command downloads packages and StarterKits.')
			->addOption('package', 'p', InputOption::VALUE_REQUIRED, 'Fetch a package from Packagist.')
		;
	}
	
		
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// find the value given to the command
		$package    = $input->getOption("package");
		
		if ($package) {
			
			// if <prompt> was passed ask the user for the package name
			if ($package == "prompt") {
				$prompt  = "what is the name of the package you want to fetch?";
				$options = "(ex. pattern-lab/plugin-kss)";
				$package = Console::promptInput($prompt,$options);
			}
			
			// make sure it looks like a valid package
			if (strpos($package,"/") === false) {
				$output->writeln('<error>that wasn\'t a valid package name. it should look like <info>pattern-lab/plugin-kss</info>...</error>');
			}
			
			// run composer via fetch
			$f = new Fetch();
			$f->fetchPackage($package);
			
			
			
		}
		
	}
	
}
