<?php

declare(strict_types=1);

namespace Koriym\Dii;

use function class_exists;
use function error_reporting;

use const E_ALL;
use const E_WARNING;

/**
 * Silent auto loader
 *
 * If the class is not found, no warning will be triggerd.
 */
final class SilentAutoload
{
    public static function autoload(string $class): bool
    {
        $e = error_reporting(E_ALL & ~E_WARNING);
        $classExists = class_exists($class);
        error_reporting($e);

        return $classExists;
    }
}
