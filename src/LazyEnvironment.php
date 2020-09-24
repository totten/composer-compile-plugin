<?php

namespace Civi\CompilePlugin;

use Civi\CompilePlugin\Util\EnvHelper;

/**
 * Class LazyEnvironment
 * @package Civi\CompilePlugin
 *
 * The lazy-loaded environment is a source of *optional* or *provisional*
 * environment variables. It's aim is to balance the following considerations:
 *
 * - For shell tasks, it's useful to reference package-locations using env-vars.
 * - The paths for packages are contingent upon root-level configuration options.
 * - There may be a large number of packages; and each package may have a long path.
 * - The space for tracking env-vars is platform-dependent (eg very diff in Win+Linux).
 *
 * To reconcile these constraints, this mechanism only exports variables that are
 * actually referenced in a shell expression.
 */
class LazyEnvironment
{

    /**
     * @var string
     */
    protected $regexp;

    /**
     * @var array
     */
    protected $vars;

    /**
     * LazyEnvironment constructor.
     *
     * @param array $vars
     *   Env-vars that may be lazily defined. Key-value pairs.
     * @param string $nameRegex
     *   A pattern that matches a variable name (excluding shell-meta chars)
     */
    public function __construct(
        $vars,
        $nameRegex = '[A-Z0-9_]+'
    ) {
        $this->regexp = ';\$(' . $nameRegex . '|\{' . $nameRegex . '\});';
        $this->vars = $vars;
    }

    /**
     * Parse the $expr. Setup the environment with any referenced variables,
     * and then execute the $callable.
     *
     * @param string $expr
     * @param callable $callable
     */
    public function wrap($expr, $callable)
    {
        $vars = $this->getVariables($expr);
        $snapshot = EnvHelper::createSnapshot(array_keys($vars));
        try {
            EnvHelper::add($vars);
            $callable();
        } finally {
            EnvHelper::restoreSnapshot($snapshot);
        }
    }

    /**
     * Determine any variables that are referenced in $expr.
     *
     * @param string $expr
     * @return array
     *   List of variables (key=>value pairs).
     */
    public function getVariables($expr)
    {
        $active = [];
        preg_replace_callback($this->regexp, function ($m) use (&$active) {
            $var = $m[1];
            if ($var{0} === '{') {
                $var = substr($var, 1, -1);
            }
            if (isset($this->vars[$var])) {
                $active[$var] = $this->vars[$var];
            }
        }, $expr);
        return $active;
    }
}
