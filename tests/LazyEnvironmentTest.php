<?php

namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\LazyEnvironment;
use Civi\CompilePlugin\Util\EnvHelper;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class LazyEnvironmentTest
 * @package Civi\CompilePlugin\Tests
 */
class LazyEnvironmentTest extends \PHPUnit\Framework\TestCase
{

    public function testGetVariables()
    {
        $e = new LazyEnvironment([
            'FOO' => 123,
            'FOOBAR' => 456,
            'BAR' => 789,
            'WHIZ_BANG' => 0,
        ]);

        $subcase = function ($cmd, $expectVars) use ($e) {
            $actualVars = $e->getVariables($cmd);
            $this->assertEquals($expectVars, $actualVars, "Resolve vars for \"$cmd\"");
        };
        $subcase('hello $FOO', ['FOO' => 123]);
        $subcase('hello $FOONOISE', []);
        $subcase('hello $NOISE$FOO$CACAPHONY', ['FOO' => 123]);
        $subcase('hello $FOOBAR', ['FOOBAR' => 456]);
        $subcase('hello $FOO-$BAR', ['FOO' => 123, 'BAR' => 789]);
        $subcase('hello ${FOO}-$BAR', ['FOO' => 123, 'BAR' => 789]);
        $subcase('hello $FOO-${BAR}', ['FOO' => 123, 'BAR' => 789]);
        $subcase('hello ${FOO}-${BAR}', ['FOO' => 123, 'BAR' => 789]);
        $subcase('hello $FOOBAR, $FOO, $BAR', ['FOOBAR' => 456, 'FOO' => 123, 'BAR' => 789]);
        $subcase('hello $FOO-$WHIZ_BANG', ['FOO' => 123, 'WHIZ_BANG' => 0]);
    }

    public function testWrap()
    {
        $e = new LazyEnvironment([
          'FOO' => 123,
          'FOOBAR' => 456,
          'BAR' => 789,
        ]);

        $this->assertEquals(false, getenv('FOO'));
        $this->assertEquals(false, getenv('FOOBAR'));
        $this->assertEquals(false, getenv('BAR'));

        $e->wrap('hello $FOO', function () {
            $this->assertEquals(123, getenv('FOO'));
            $this->assertEquals(false, getenv('FOOBAR'));
            $this->assertEquals(false, getenv('BAR'));
        });

        $this->assertEquals(false, getenv('FOO'));
        $this->assertEquals(false, getenv('FOOBAR'));
        $this->assertEquals(false, getenv('BAR'));

        $e->wrap('hello $FOOBAR$BAR', function () {
            $this->assertEquals(false, getenv('FOO'));
            $this->assertEquals(456, getenv('FOOBAR'));
            $this->assertEquals(789, getenv('BAR'));
        });

        $this->assertEquals(false, getenv('FOO'));
        $this->assertEquals(false, getenv('FOOBAR'));
        $this->assertEquals(false, getenv('BAR'));
    }
}
