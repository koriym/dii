<?php

use Composer\Autoload\ClassLoader;

// Yii's error handler turns PHP 8.5's "(double) cast is deprecated" into an
// HTML fatal. Drop E_DEPRECATED so the handler (set with error_reporting())
// ignores deprecations from the framework.
error_reporting(E_ALL & ~E_DEPRECATED);

$loader = require __DIR__ . '/vendor/autoload.php';
assert($loader instanceof ClassLoader);
$loader->addPsr4('Koriym\\Dii\\', __DIR__ . '/tests/Fake');
