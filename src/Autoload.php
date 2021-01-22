<?php

declare(strict_types=1);

namespace Koriym\Dii;

/**
 * Silent auto loader
 *
 * If the class is not found, no warning will be triggerd.
 */
final class Autoload
{
    public static function autoload(string $class): bool
    {
        $e = error_reporting(E_ALL & ~E_WARNING);
        $classExists = class_exists($class);
        error_reporting($e);

        return $classExists;
    }
}
