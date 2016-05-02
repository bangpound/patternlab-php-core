<?php

/*!
 * Console Export Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Exports patterns w/out pattern lab-specific mark-up. Also moves user-generated static
 * assets from public/ to export/
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\FileUtil;
use \PatternLab\Generator;
use \PatternLab\Timer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command {
	
	protected function configure() {
		$this->setName('export')
		->setDescription('Export Pattern Lab patterns & assets')
		->setHelp('The export command generates your patterns without Pattern Lab\'s CSS & JS, copies static assets from <path>public/</path>, and puts all of it in <path>export/</path>.')
			->addOption('clean', null, InputOption::VALUE_NONE, 'Don\'t add any header or footer mark-up to the exported patterns.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		// set-up required vars
		$options                = array();
		$options["exportFiles"] = true;
		$options["exportClean"] = $input->getOption("clean");
		$options["moveStatic"]  = false;
		
		FileUtil::cleanExport();
		
		$g = new Generator();
		$g->generate($options);
		
		FileUtil::exportStatic();
		
	}
	
}
