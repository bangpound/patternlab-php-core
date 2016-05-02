<?php

/*!
 * Console Config Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Generator;
use \PatternLab\Timer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigCommand extends Command {
	
	protected function configure() {
		$this
			->setName('config')
			->setDescription('Configure Pattern Lab')
			->setHelp('The --config command allows for the review and update of existing Pattern Lab config options.')
			->addOption('get', null, InputOption::VALUE_REQUIRED, 'Get the value for a specific config option.')
			->addOption('list', null, InputOption::VALUE_NONE, 'List the current config options.')
			->addOption('set', null, InputOption::VALUE_REQUIRED, 'Set the value for a specific config option.')
		;
	}
	
	public function execute(InputInterface $input, OutputInterface $output) {
		
		if ($input->getOption('list')) {
			
			// get all of the options
			$options = Config::getOptions();
			
			// sort 'em alphabetically
			ksort($options);
			
			// find length of longest option
			$lengthLong = 0;
			foreach ($options as $optionName => $optionValue) {
				$lengthLong = (strlen($optionName) > $lengthLong) ? strlen($optionName) : $lengthLong;
			}
			
			// iterate over each option and spit it out
			$table = new Table($output);
			$table->setHeaders(['name', 'value']);
			foreach ($options as $optionName => $optionValue) {
				$optionValue = (is_array($optionValue)) ? implode(", ",$optionValue) : $optionValue;
				$optionValue = (!$optionValue) ? "false" : $optionValue;
				$table->addRow([$optionName, $optionValue]);
			}
			$table->render();
			
		} else if ($input->getOption("get")) {
			
			// figure out which optino was passed
			$searchOption = $input->getOption("get");
			$optionValue  = Config::getOption($searchOption);
			
			// write it out
			if (!$optionValue) {
				$output->writeln("<error>the --get value you provided, <info>".$searchOption."</info>, does not exists in the config...</error>");
			} else {
				$optionValue = (is_array($optionValue)) ? implode(", ",$optionValue) : $optionValue;
				$optionValue = (!$optionValue) ? "false" : $optionValue;
				$output->writeln('<info>'.$searchOption.": <ok>".$optionValue."</ok></info>");
			}
			
		} else if ($input->getOption("set")) {
			
			// find the value that was passed
			$updateOption = $input->getOption("set");
			$updateOptionBits = explode("=",$updateOption);
			if (count($updateOptionBits) == 1) {
				$output->writeln("<error>the --set value should look like <info>optionName=\"optionValue\"</info>. nothing was updated...</error>");
			} 
			
			// set the name and value that were passed
			$updateName   = $updateOptionBits[0];
			$updateValue  = (($updateOptionBits[1][0] == "\"") || ($updateOptionBits[1][0] == "'")) ? substr($updateOptionBits[1],1,strlen($updateOptionBits[1])-1) : $updateOptionBits[1];
			
			// make sure the option being updated already exists
			$currentValue = Config::getOption($updateName);
			
			if (!$currentValue) {
				$output->writeln("<error>the --set option you provided, <info>".$updateName."</info>, does not exists in the config. nothing will be updated...</error>");
			} else {
				Config::updateConfigOption($updateName,$updateValue);
			}
			
		}
		
	}
	
}
