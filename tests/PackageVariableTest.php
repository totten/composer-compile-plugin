<?php

namespace Civi\CompilePlugin\Tests;

use Civi\CompilePlugin\Event\CompileEvents;
use ProcessHelper\ProcessHelper as PH;

/**
 * Class PackageVariableTest
 * @package Civi\CompilePlugin\Tests
 *
 * This is general integration test of the plugin. It creates an example project which uses the
 * current/under-development plugin.  It asserts that various events fire.
 *
 * We check this by having each event echo some text of the form `MARK: something-happend`.
 * We then do an assertion on the list of `^MARK:` statements.
 */
class PackageVariableTest extends IntegrationTestCase
{

    public static function getComposerJson()
    {
        return parent::getComposerJson() + [
          'name' => 'test/pkgvar-test',
          'require' => [
              'civicrm/composer-compile-plugin' => '@dev',
              'test/cherry-jam' => '@dev',
          ],
          'minimum-stability' => 'dev',
          'extra' => [
            'compile' => [
              [
                  'title' => 'Show some variables',
                  'shell' => [
                      'echo PKG:root=$PKG__ROOT',
                      'echo PKG:test/cherry-jam=$PKG__TEST__CHERRY_JAM',
                      'echo PKG:test/pkgvar-test=$PKG__TEST__PKGVAR_TEST',
                  ],
              ],
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
     * When running 'composer install', it run various events.
     */
    public function testComposerInstall()
    {
        $p = PH::runOk('COMPOSER_COMPILE_PASSTHRU=always COMPOSER_COMPILE=1 composer install');

        $actualLines = array_values(preg_grep(
            ';^PKG:;',
            explode("\n", $p->getOutput())
        ));

        $expectLines = [
           "PKG:root=" . self::getTestDir(),
            "PKG:test/cherry-jam=" . self::getTestDir() . '/vendor/test/cherry-jam',
            "PKG:test/pkgvar-test=" . self::getTestDir(),
        ];

        $this->assertEquals($expectLines, $actualLines);
    }
}
