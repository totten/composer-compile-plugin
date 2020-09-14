<?php

namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\Util\EnvHelper;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class ErrorTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It creates an example project which uses the
 * current/under-development plugin.  It asserts that various events fire.
 *
 * We check this by having each event echo some text of the form `MARK: something-happend`.
 * We then do an assertion on the list of `^MARK:` statements.
 */
class ErrorTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
          'name' => 'test/error-test',
          'require' => [
              'civicrm/composer-compile-plugin' => '@dev',
          ],
          'minimum-stability' => 'dev',
          'extra' => [
            'compile' => [
              [
                  'title' => 'Compile first',
                  'shell' => 'echo MARK: RUN FIRST; [ -n $ERROR_1 ]; exit $ERROR_1',
              ],
              [
                  'title' => 'Compile second',
                  'shell' => 'echo MARK: RUN SECOND; [ -n $ERROR_2 ]; exit $ERROR_2',
              ],
              [
                  'title' => 'Compile third',
                  'shell' => 'echo MARK: RUN THIRD; [ -n $ERROR_3 ]; exit $ERROR_3',
              ]
            ],
          ],
        ];
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::initTestProject(static::getComposerJson());
    }

    /**
     * Run a successful command, with passthru=always
     */
    public function testPassthruAlways()
    {
        putenv('COMPOSER_COMPILE_PASSTHRU=always');
        $p = PH::runOk('COMPOSER_COMPILE=1 composer install');
        $expectLines = [
          "^MARK: RUN FIRST",
          "^MARK: RUN SECOND",
          "^MARK: RUN THIRD",
        ];
        $this->assertOutputLines($expectLines, ';^MARK:;', $p->getOutput());
    }

    /**
     * Run a successful command, with passthru=always
     */
    public function testPassthruError()
    {
        putenv('COMPOSER_COMPILE_PASSTHRU=error');
        putenv('ERROR_2');
        $p = PH::runOk('COMPOSER_COMPILE=1 composer install');
        $expectLines = [
          "^MARK: RUN SECOND",
        ];
        $this->assertOutputLines($expectLines, ';^MARK:;', $p->getOutput());
    }

    /**
     * @param array $expectLines
     * @param string $outputFilter
     *   A regexp to identify output lines that are interesting.
     * @param string $actualOutput
     *   The full command output
     */
    protected function assertOutputLines($expectLines, $outputFilter, $actualOutput)
    {
        $actualLines = array_values(preg_grep($outputFilter,
          explode("\n", $actualOutput)));

        $serialize = print_r([
          'expect' => $expectLines,
          'actual' => $actualLines
        ], 1);

        $this->assertEquals(count($expectLines), count($actualLines),
          "Compare line count in $serialize");
        foreach ($expectLines as $offset => $expectLine) {
            $this->assertRegExp(";$expectLine;", $actualLines[$offset],
              "Check line $offset in $serialize");
        }
    }
}
