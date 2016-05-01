<?php

/*!
 * Console Generate Command Class
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command {
	
	protected function configure()
	{
		$this
			->setName('generate')
			->setDescription('Generate Pattern Lab')
			->setHelp('The generate command generates an entire site a single time. By default it removes old content in <path>public/</path>, compiles the patterns and moves content from <path>source/</path> into <path>public/</path>')
			->addOption('patternsonly', 'p', InputOption::VALUE_NONE, 'Generate only the patterns. Does NOT clean <path>public/</path>.')
			->addOption('nocache', 'nc', InputOption::VALUE_NONE, 'Set the cacheBuster value to 0.')
		;
	}
	
		
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// set-up required vars
		$options                  = array();
		$options["moveStatic"]    = ($input->getOption("patternsonly")) ? false : true;
		$options["noCacheBuster"] = $input->getOption("nocache");
		
		$g = new Generator();
		$g->generate($options);
		$g->printSaying();
		
	}
	
}
