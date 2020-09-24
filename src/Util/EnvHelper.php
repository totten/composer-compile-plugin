<?php
namespace Civi\CompilePlugin\Util;

class EnvHelper
{

    /**
     * @return string[]
     *   Key-value pairs.
     */
    public static function getAll()
    {
        // Huzza, PHP 7.1
        return getenv();
    }

    /**
     * Set the full environment, precisely.
     *
     * @param array $vars
     *   The new environment. Key-value pairs.
     *   All other environment variables will be removed.
     */
    public static function setAll($vars)
    {
        $current = self::getAll();
        $removed = array_diff(array_keys($current), array_keys($vars));
        foreach ($removed as $key) {
            putenv("$key");
        }
        self::add($vars);
    }

    /**
     * Add variables to the environment.
     *
     * @param array $vars
     *   The new environment. Key-value pairs.
     */
    public static function add($vars)
    {
        foreach ($vars as $key => $value) {
            putenv("$key=$value");
        }
    }

    /**
     * Take a snapshot of the value (or non-value) of the given keys.
     *
     * @param array $keys
     * @return array
     */
    public static function createSnapshot($keys)
    {
        $snapshot = [];
        foreach ($keys as $key) {
            if (getenv($key) !== false) {
                $snapshot[] = "$key=" . getenv($key);
            } else {
                $snapshot[] = "$key";
            }
        }
        return $snapshot;
    }

    /**
     * @param array $snapshot
     * @see createSnapshot
     */
    public static function restoreSnapshot($snapshot)
    {
        foreach ($snapshot as $varExpr) {
            putenv($varExpr);
        }
    }
}
