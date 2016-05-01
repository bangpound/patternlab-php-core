<?php

/*!
 * Console Watch Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Console\Command;
use \PatternLab\Generator;
use \PatternLab\Timer;
use \PatternLab\Watcher;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WatchCommand extends Command {

	protected function configure()
	{
		$this
			->setName('watch')
			->setDescription('Watch for changes and regenerate')
			->setHelp('The watch command builds Pattern Lab, watches for changes in <path>source/</path> and regenerates Pattern Lab when there are any.')
			->addOption('patternsonly', 'p', InputOption::VALUE_NONE, 'Watches only the patterns. Does NOT clean <path>public/</path>.')
			->addOption('nocache', 'nc', InputOption::VALUE_NONE, 'Set the cacheBuster value to 0.')
			->addOption('sk', null, InputOption::VALUE_NONE, 'Watch for changes to the StarterKit and copy to <path>source/</path>. The <info>--sk</info> flag should only be used if one is actively developing a StarterKit.')
			->addUsage('--patternsonly --nocache # To watch only patterns and turn off the cache buster.')
		;
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		
		// set-up required vars
		$options                  = array();
		$options["moveStatic"]    = ($input->getOption("patternsonly")) ? false : true;
		$options["noCacheBuster"] = $input->getOption("nocache");
		
		// DEPRECATED
		// $options["autoReload"]    = Console::findCommandOption("r|autoreload");
		
		// see if the starterKit flag was passed so you know what dir to watch
		if ($input->getOption("sk")) {
			
			// load the starterkit watcher
			$w = new Watcher();
			$w->watchStarterKit();
			
		} else {
			
			// load the generator
			$g = new Generator();
			$g->generate($options);
			
			// load the watcher
			$w = new Watcher();
			$w->watch($options);
			
		}
		
		
	}
	
}
