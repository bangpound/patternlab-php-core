<?php

namespace PatternLab\Pimple;

use PatternLab\Config;
use PatternLab\Console\Commands as Commands;
use PatternLab\Console\Event;
use PatternLab\Dispatcher;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Helper\DebugFormatterHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServiceProvider implements ServiceProviderInterface, EventListenerProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['config'] = function (Container $c) {
            Config::init($c['config.baseDir'], $c['config.verbose']);
            return Config::getOptions();
        };

        $pimple['dispatcher'] = function (Container $c) {
            Dispatcher::init();
            return Dispatcher::getInstance();
        };

        $pimple['console.input'] = function () {
            return new ArgvInput();
        };

        $pimple['console.output'] = function () {
            return new ConsoleOutput();
        };

        $pimple['console.application'] = function (Container $c) {
            $app = new Application('Pattern Lab', Config::getOption('v'));
            $app->setDispatcher($c['dispatcher']);
            $app->setHelperSet($c['console.helper_set']);

            return $app;
        };

        $pimple['console.helper_set'] = function (Container $c) {
            return new HelperSet(array(
              new FormatterHelper(),
              new DebugFormatterHelper(),
              new ProcessHelper(),
              new QuestionHelper(),
            ));
        };

        $pimple['command.config'] = function () {
            return new Commands\ConfigCommand();
        };
        $pimple['command.export'] = function () {
            return new Commands\ExportCommand();
        };
        $pimple['command.fetch'] = function () {
            return new Commands\FetchCommand();
        };
        $pimple['command.generate'] = function () {
            return new Commands\GenerateCommand();
        };
        $pimple['command.server'] = function () {
            return new Commands\ServerCommand();
        };
        $pimple['command.starter_kit'] = function () {
            return new Commands\StarterKitCommand();
        };
        $pimple['command.version'] = function () {
            return new Commands\VersionCommand();
        };
        $pimple['command.watch'] = function () {
            return new Commands\WatchCommand();
        };

        $pimple->extend('console.application', function (Application $app, Container $c) {

            /** @var array $command_ids */
            $command_ids = array_filter($c->keys(), function ($id) {
                return strpos($id, 'command.') === 0;
            });

            array_walk($command_ids, function ($id) use ($app, $c) {
                /* @var Application $app */
                $app->add($c[$id]);
            });

            return $app;
        });

        $pimple->extend('dispatcher', function (EventDispatcher $dispatcher, Container $c) {
            $this->subscribe($c, $dispatcher);

            return $dispatcher;
        });
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        $dispatcher->addListener('console.command', function (ConsoleCommandEvent $event) use ($dispatcher) {
            $patternLabEvent = new Event($options = array());

            // send out an event
            $dispatcher->dispatch('console.loadCommandsStart', $patternLabEvent);

            // loadCommands
            //self::loadCommands();

            // send out an event
            $dispatcher->dispatch('console.loadCommandsEnd', $patternLabEvent);
            // TODO: Implement subscribe() method.
        });
    }
}
