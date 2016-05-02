<?php

namespace PatternLab\Console\Commands;

use PatternLab\Config;
use PatternLab\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class UpdateCommand extends Command
{
    protected function configure()
    {
        $this->setName('update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configs = include Config::getOption('packagesDir').'/config.php';

        foreach ($configs as $config) {
            // see if we need to modify the config
            if (isset($config['config'])) {
                foreach ($config['config'] as $option => $value) {
                    // update the config option
                    Config::updateConfigOption($option, $value);
                }
            }
        }

        $paths = include Config::getOption('packagesDir').'/paths.php';

        foreach ($configs as $name => $config) {
            $pathDist = $paths[$name].'/dist/';
            self::resolveDist($config, $name, $pathDist);
        }
    }

    /**
     * Parse the extra section from composer.json.
     *
     * @param $config array the JSON for the composer extra section
     * @param $name string
     * @param $pathDist string
     */
    private static function resolveDist(array $config, $name, $pathDist)
    {
        $dirs = ['baseDir', 'publicDir', 'sourceDir', 'scriptsDir', 'dataDir'];
        foreach ($dirs as $dir) {
            // move assets to the base directory
            if (isset($config['dist'][$dir])) {
                self::parseFileList($name, $pathDist, Config::getOption($dir), $config['dist'][$dir]);
            }
        }

        // move assets to the components directory
        if (isset($config['dist']['componentDir'])) {
            $templateExtension = isset($config['templateExtension']) ? $config['templateExtension'] : 'mustache';
            $onready = isset($config['onready']) ? $config['onready'] : '';
            $callback = isset($config['callback']) ? $config['callback'] : '';
            $componentDir = Config::getOption('componentDir');
            self::parseComponentList($name, $pathDist, $componentDir.'/'.$name, $config['dist']['componentDir'], $templateExtension, $onready, $callback);
            self::parseFileList($name, $pathDist, $componentDir.'/'.$name, $config['dist']['componentDir']);
        }
    }

    /**
     * Parse the component types to figure out what needs to be added to the component JSON files.
     *
     * @param  {String}    the name of the package
     * @param  {String}    the base directory for the source of the files
     * @param  {String}    the base directory for the destination of the files (publicDir or sourceDir)
     * @param  {Array}     the list of files to be parsed for component types
     * @param  {String}    template extension for templates
     * @param  {String}    the javascript to run on ready
     * @param  {String}    the javascript to run as a callback
     */
    private static function parseComponentList($packageName, $sourceBase, $destinationBase, $componentFileList, $templateExtension, $onready, $callback)
    {

        /*
        iterate over a source or source dirs and copy files into the componentdir.
        use file extensions to add them to the appropriate type arrays below. so...
            "patternlab": {
                "dist": {
                    "componentDir": {
                        { "*": "*" }
                    }
                }
                "onready": ""
                "callback": ""
                "templateExtension": ""
            }
        }

        */

        // decide how to type list files. the rules:
        // src        ~ dest        -> action
        // *          ~ *           -> iterate over all files in {srcroot}/ and create a type listing
        // foo/*      ~ path/*      -> iterate over all files in {srcroot}/foo/ and create a type listing
        // foo/s.html ~ path/k.html -> create a type listing for {srcroot}/foo/s.html

        // set-up component types store
        $componentTypes = array('stylesheets' => array(), 'javascripts' => array(), 'templates' => array());

        // iterate over the file list
        foreach ($componentFileList as $componentItem) {

            // retrieve the source & destination
            $source = self::removeDots(key($componentItem));
            $destination = self::removeDots($componentItem[$source]);

            if (($source == '*') || ($source[strlen($source) - 1] == '*')) {

                // build the source & destination
                $source = (strlen($source) > 2)      ? rtrim($source, '/*') : '';
                $destination = (strlen($destination) > 2) ? rtrim($destination, '/*') : '';

                // get files
                $finder = new Finder();
                $finder->files()->in($sourceBase.$source);

                // iterate over the returned objects
                /** @var SplFileInfo $file */
                foreach ($finder as $file) {
                    $ext = $file->getExtension();

                    if ($ext == 'css') {
                        $componentTypes['stylesheets'][] = str_replace($sourceBase.$source, $destination, $file->getPathname());
                    } elseif ($ext == 'js') {
                        $componentTypes['javascripts'][] = str_replace($sourceBase.$source, $destination, $file->getPathname());
                    } elseif ($ext == $templateExtension) {
                        $componentTypes['templates'][] = str_replace($sourceBase.$source, $destination, $file->getPathname());
                    }
                }
            } else {
                $bits = explode('.', $source);

                if (count($bits) > 0) {
                    $ext = $bits[count($bits) - 1];

                    if ($ext == 'css') {
                        $componentTypes['stylesheets'][] = $destination;
                    } elseif ($ext == 'js') {
                        $componentTypes['javascripts'][] = $destination;
                    } elseif ($ext == $templateExtension) {
                        $componentTypes['templates'][] = $destination;
                    }
                }
            }
        }

        /*
        FOR USE AS A PACKAGE TO BE LOADED LATER
        {
            "name": "pattern-lab-plugin-kss",
            "templates": { "filename": "filepath" }, // replace slash w/ dash in filename. replace extension
            "stylesheets": [ ],
            "javascripts": [ ],
            "onready": "",
            "callback": ""
        }
        */
        $packageInfo = array();
        $packageInfo['name'] = $packageName;
        $packageInfo['templates'] = array();
        foreach ($componentTypes['templates'] as $templatePath) {
            $templateKey = preg_replace("/\W/", '-', str_replace('.'.$templateExtension, '', $templatePath));
            $packageInfo['templates'][$templateKey] = $templatePath;
        }
        $packageInfo['stylesheets'] = $componentTypes['stylesheets'];
        $packageInfo['javascripts'] = $componentTypes['javascripts'];
        $packageInfo['onready'] = $onready;
        $packageInfo['callback'] = $callback;
        $packageInfoPath = Config::getOption('componentDir').'/packages/'.str_replace('/', '-', $packageName).'.json';

        // double-check the dirs are created
        if (!is_dir(Config::getOption('componentDir'))) {
            mkdir(Config::getOption('componentDir'));
        }

        if (!is_dir(Config::getOption('componentDir').'/packages/')) {
            mkdir(Config::getOption('componentDir').'/packages/');
        }

        // write out the package info
        file_put_contents($packageInfoPath, json_encode($packageInfo));
    }

    /*
     * Move the files from the package to their location in the public dir or source dir
     * @param  {String}    the name of the package
     * @param  {String}    the base directory for the source of the files
     * @param  {String}    the base directory for the destintation of the files (publicDir or sourceDir)
     * @param  {Array}     the list of files to be moved
     */
    private static function parseFileList($packageName, $sourceBase, $destinationBase, $fileList)
    {
        foreach ($fileList as $fileItem) {

            // retrieve the source & destination
            $source = self::removeDots(key($fileItem));
            $destination = self::removeDots($fileItem[$source]);

            // depending on the source handle things differently. mirror if it ends in /*
            self::moveFiles($source, $destination, $packageName, $sourceBase, $destinationBase);
        }
    }

    /*
     * Parse the component types to figure out what needs to be moved and added to the component JSON files
     * @param  {String}    file path to move
     * @param  {String}    file path to move to
     * @param  {String}    the name of the package
     * @param  {String}    the base directory for the source of the files
     * @param  {String}    the base directory for the destination of the files (publicDir or sourceDir)
     * @param  {Array}     the list of files to be moved
     */
    private static function moveFiles($source, $destination, $packageName, $sourceBase, $destinationBase)
    {
        $fs = new Filesystem();

        // make sure the destination base exists
        if (!is_dir($destinationBase)) {
            $fs->mkdir($destinationBase);
        }

        // clean any * or / on the end of $destination
        $destination = (($destination != '*') && ($destination[strlen($destination) - 1] == '*')) ? substr($destination, 0, -1) : $destination;
        $destination = ($destination[strlen($destination) - 1] == '/') ? substr($destination, 0, -1) : $destination;

        // decide how to move the files. the rules:
        // src        ~ dest        -> action
        // *          ~ *           -> mirror all in {srcroot}/ to {destroot}/
        // *          ~ path/*      -> mirror all in {srcroot}/ to {destroot}/path/
        // foo/*      ~ path/*      -> mirror all in {srcroot}/foo/ to {destroot}/path/
        // foo/s.html ~ path/k.html -> mirror {srcroot}/foo/s.html to {destroot}/path/k.html

        if (($source == '*') && ($destination == '*')) {
            $result = self::pathExists($packageName, $destinationBase.DIRECTORY_SEPARATOR);
            $options = ($result) ? array('delete' => true, 'override' => true) : array('delete' => false, 'override' => false);
            $fs->mirror($sourceBase, $destinationBase.DIRECTORY_SEPARATOR, null, $options);
        } elseif ($source == '*') {
            $result = self::pathExists($packageName, $destinationBase.DIRECTORY_SEPARATOR.$destination);
            $options = ($result) ? array('delete' => true, 'override' => true) : array('delete' => false, 'override' => false);
            $fs->mirror($sourceBase, $destinationBase.DIRECTORY_SEPARATOR.$destination, null, $options);
        } elseif ($source[strlen($source) - 1] == '*') {
            $source = rtrim($source, '/*');
            $result = self::pathExists($packageName, $destinationBase.DIRECTORY_SEPARATOR.$destination);
            $options = ($result) ? array('delete' => true, 'override' => true) : array('delete' => false, 'override' => false);
            $fs->mirror($sourceBase.$source, $destinationBase.DIRECTORY_SEPARATOR.$destination, null, $options);
        } else {
            $pathInfo = explode(DIRECTORY_SEPARATOR, $destination);
            $destinationDir = implode(DIRECTORY_SEPARATOR, $pathInfo);
            if (!$fs->exists($destinationBase.DIRECTORY_SEPARATOR.$destinationDir)) {
                $fs->mkdir($destinationBase.DIRECTORY_SEPARATOR.$destinationDir);
            }
            $result = self::pathExists($packageName, $destinationBase.DIRECTORY_SEPARATOR.$destination);
            $override = ($result) ? true : false;
            $fs->copy($sourceBase.$source, $destinationBase.DIRECTORY_SEPARATOR.$destination, $override);
        }
    }

    /*
     * Remove dots from the path to make sure there is no file system traversal when looking for or writing files
     * @param  {String}    the path to check and remove dots
     *
     * @return {String}    the path minus dots
     */
    private static function removeDots($path)
    {
        $parts = array();
        foreach (explode('/', $path) as $chunk) {
            if (('..' !== $chunk) && ('.' !== $chunk) && ('' !== $chunk)) {
                $parts[] = $chunk;
            }
        }

        return implode('/', $parts);
    }

    /*
     * Check to see if the path already exists. If it does prompt the user to double-check it should be overwritten
     * @param  {String}    the package name
     * @param  {String}    path to be checked
     *
     * @return {Boolean}   if the path exists and should be overwritten
     */
    private static function pathExists($packageName, $path)
    {
        $fs = new Filesystem();

        if ($fs->exists($path)) {

            // set-up a human readable prompt
            $humanReadablePath = str_replace(Config::getOption('baseDir'), './', $path);

            // set if the prompt should fire
            $prompt = true;

            // are we checking a directory?
            if (is_dir($path)) {

                // see if the directory is essentially empty
                $files = scandir($path);
                foreach ($files as $key => $file) {
                    $ignore = array('..', '.', '.gitkeep', 'README', '.DS_Store');
                    $file = explode('/', $file);
                    if (in_array($file[count($file) - 1], $ignore)) {
                        unset($files[$key]);
                    }
                }

                if (empty($files)) {
                    $prompt = false;
                }
            }

            if ($prompt) {

                // prompt for input using the supplied query
                $prompt = 'the path <path>'.$humanReadablePath.'</path> already exists. merge or replace with the contents of <path>'.$packageName.'</path> package?';
                $options = 'M/r';
                $input = Console::promptInput($prompt, $options);

                if ($input == 'm') {
                    Console::writeTag('ok', 'contents of <path>'.$humanReadablePath."</path> have been merged with the package's content...", false, true);

                    return false;
                } else {
                    Console::writeWarning('contents of <path>'.$humanReadablePath."</path> have been replaced by the package's content...", false, true);

                    return true;
                }
            }

            return false;
        }

        return false;
    }
}
