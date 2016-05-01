<?php

/*!
 * Console Server Command Class
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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerCommand extends Command {
	
		
		
	protected function configure()
	{
		$this
			->setName('server')
			->setDescription('Start the PHP-based server')
			->setHelp('The server command will start PHP\'s web server for you.')
			->addOption('host', null, InputOption::VALUE_REQUIRED, 'Provide a custom hostname.', 'localhost')
			->addOption('port', null, InputOption::VALUE_REQUIRED, 'Provide a custom port.', 8080)
			->addUsage('--host <host> --port <port> # To provide both a custom hostname and port:')
		;
	}
	
		
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (version_compare(phpversion(), '5.4.0', '<')) {
			
			$output->writeln("<warning>you must have PHP 5.4.0 or greater to use this feature. you are using PHP ".phpversion()."...</warning>");
			
		} else {
			
			// set-up defaults
			$publicDir = Config::getOption("publicDir");
			$coreDir   = Config::getOption("coreDir");
			
			$host = $input->getOption("host");
			$host = $host ? $host : "localhost";
			
			$port = $input->getOption("port");
			$host = $port ? $host.":".$port : $host.":8080";
			
			// start-up the server with the router
			$output->writeln("<info>server started on ".$host.". use ctrl+c to exit...</info>");
			passthru("cd ".$publicDir." && ".$_SERVER["_"]." -S ".$host." ".$coreDir."/server/router.php");
			
		}
		
	}
	
}
