<?php

/*!
 * Console Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Handles the set-up of the console commands, options, and documentation
 * Heavily influenced by the symfony/console output format
 *
 */

namespace PatternLab;

use \Colors\Color;
use \PatternLab\Console\Event as ConsoleEvent;
use \PatternLab\Dispatcher;
use \PatternLab\Timer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Console {
	
	protected static $commands         = array();
	protected static $commandInstances = array();
	protected static $options          = array();
	protected static $optionsShort     = "";
	protected static $optionsLong      = array();
	private   static $color;
	private   static $self             = "";
	private   static $zTracker         = 1;
	
	/**
	 * @var InputInterface
	 */
	private static $input;

	/**
	 * @var OutputInterface
	 */
	private static $output;

	public static function init(InputInterface $input, OutputInterface $output) {
		self::$input = $input;
		self::$output = $output;
		
		// double-check this is being run from the command line
		if (php_sapi_name() != 'cli') {
			print "The console can only be run from the command line.\n";
			exit;
		}
		
		self::$self = $_SERVER["PHP_SELF"];
		
		// set-up the cli coloring
		self::$color = new Color();
		
		// define the pattern lab color theme
		$colorTheme = array();
		$colorTheme["h1"]       = "bold";
		$colorTheme["h2"]       = "underline";
		$colorTheme["optional"] = "italic";
		$colorTheme["desc"]     = "green";
		$colorTheme["path"]     = "green";
		$colorTheme["enter"]    = "blue";
		$colorTheme["ok"]       = "green";
		$colorTheme["options"]  = "magenta";
		$colorTheme["info"]     = "cyan";
		$colorTheme["warning"]  = "yellow";
		$colorTheme["error"]    = "red";
		$colorTheme["strong"]   = "bold";
		self::$color->setTheme($colorTheme);
		
	}
	
	/**
	* Return the command that was given in the command line arguments
	*
	* @return {String}      the command. passes false if no command was found
	*/
	public static function getCommand() {
		foreach (self::$commands as $command => $attributes) {
			if (isset(self::$options[$command]) || isset(self::$options[$attributes["commandShort"]])) {
				return $command;
			}
		}
		return false;
	}
	
	/**
	* Load all of the rules related to Pattern Data
	*/
	public static function loadCommands() {
		foreach (glob(__DIR__."/Console/Commands/*.php") as $filename) {
			$command = str_replace(".php","",str_replace(__DIR__."/Console/Commands/","",$filename));
			if ($command[0] != "_") {
				$commandClass = "\PatternLab\Console\Commands\\".$command;
				self::$commandInstances[] = new $commandClass();
			}
		}
	}
	
	/**
	* Make sure the space is properly set between long command options and short command options
	* @param  {Integer}       the longest length of the command's options
	* @param  {Integer}       the character length of the given option
	*/
	public static function getSpacer($lengthLong,$itemLongLength) {
		$i            = 0;
		$spacer       = " ";
		$spacerLength = $lengthLong - $itemLongLength;
		while ($i < $spacerLength) {
			$spacer .= " ";
			$i++;
		}
		return $spacer;
	}
	
	/**
	* Make a path human readable by dropping the base dir
	* @param  {String}        the path to clean
	*
	* @return {String}        cleaned up path
	*/
	public static function getHumanReadablePath($path) {
		return str_replace(Config::getOption("baseDir"), "./", $path);
	}
	
	/**
	* Modify a line to include the given tag by default
	* @param  {String}        the content to be written out
	*/
	public static function addTags($line,$tag) {
		$lineFinal = "<".$tag.">".preg_replace("/<[A-z0-9-_]{1,}>[^<]{1,}<\/[A-z0-9-_]{1,}>/","</".$tag.">$0<".$tag.">",$line)."</".$tag.">";
		return $lineFinal;
	}
	
	/**
	* Write out a line to the console with info tags
	* @param  {String}        the content to be written out
	* @param  {Boolean}       if there should be two spaces added to the beginning of the line
	* @param  {Boolean}       if there should be two breaks added to the end of the line
	*/
	public static function writeInfo($line,$doubleSpace = false,$doubleBreak = false) {
		$lineFinal = self::addTags($line,"info");
		self::writeLine($lineFinal,$doubleSpace,$doubleBreak);
	}
	
	/**
	* Write out a line to the console
	* @param  {String}        the content to be written out
	* @param  {Boolean}       if there should be two spaces added to the beginning of the line
	* @param  {Boolean}       if there should be two breaks added to the end of the line
	*/
	public static function writeLine($line,$doubleSpace = false,$doubleBreak = false) {
		$break = ($doubleBreak) ? PHP_EOL.PHP_EOL : PHP_EOL;
		if (strpos($line,"<nophpeol>") !== false) {
			$break = "";
			$line  = str_replace("<nophpeol>","",$line);
		}
		$space = ($doubleSpace) ? "  " : "";
		$c     = self::$color;
		self::$output->write($space.$c($line)->colorize().$break);
	}
	
	/**
	* Write out a line to the console with a specific tag
	* @param  {String}        the tag to add to the line
	* @param  {String}        the content to be written out
	* @param  {Boolean}       if there should be two spaces added to the beginning of the line
	* @param  {Boolean}       if there should be two breaks added to the end of the line
	*/
	public static function writeTag($tag,$line,$doubleSpace = false,$doubleBreak = false) {
		$lineFinal = self::addTags($line,$tag);
		self::writeLine($lineFinal,$doubleSpace,$doubleBreak);
	}
	
	/**
	* Write out a line to the console with warning tags
	* @param  {String}        the content to be written out
	* @param  {Boolean}       if there should be two spaces added to the beginning of the line
	* @param  {Boolean}       if there should be two breaks added to the end of the line
	*/
	public static function writeWarning($line,$doubleSpace = false,$doubleBreak = false) {
		$lineFinal = self::addTags($line,"warning");
		self::writeLine($lineFinal,$doubleSpace,$doubleBreak);
	}
	
	/**
	* Prompt the user for some input
	* @param  {String}        the text for the prompt
	* @param  {String}        the text for the options
	* @param  {Boolean}       if we should lowercase the input before sending it back
	* @param  {String}        the tag that should be used when drawing the content
	*
	* @return {String}        trimmed input given by the user
	*/
	public static function promptInput($prompt = "", $options = "", $lowercase = true, $tag = "info") {
		
		// check prompt
		if (empty($prompt)) {
			throw new \RuntimeException("an input prompt requires prompt text...");
		}
		
		// if there are suggested options add them
		if (!empty($options)) {
			$prompt .= " <options>".$options."</options> >";
		}
		
		// make sure no end-of-line is added
		$prompt .= " <nophpeol>";
		
		// open the terminal and wait for feedback
		$stdin = fopen("php://stdin", "r");
		Console::writeTag($tag,$prompt);
		$input = trim(fgets($stdin));
		fclose($stdin);
		
		// check to see if it should be lowercased before sending back
		return ($lowercase) ? strtolower($input) : $input;
		
	}
	
}
