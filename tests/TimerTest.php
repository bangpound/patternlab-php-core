<?php

namespace PatternLab\Tests;

use PatternLab\Console;
use PatternLab\Timer;

class TimerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Console::init();
    }

    public function testStop()
    {
        Timer::start();
        Timer::stop();
        $this->expectOutputRegex('/site generation took/');
    }

    public function testCheck()
    {
        Timer::start();
        Timer::check();
        $this->expectOutputRegex('/currently taken/');
        Timer::stop();
    }

    public function testCheckArg()
    {
        Timer::start();
        Timer::check('test');
        $this->expectOutputRegex('/test/');
        Timer::stop();
    }
}
