<?php

use Composer\Autoload\ClassLoader;

passthru('cd demo && composer install --quiet');
$loader = require __DIR__ . '/vendor/autoload.php';
assert($loader instanceof ClassLoader);
$loader->addPsr4('Koriym\\Dii\\', __DIR__ . '/tests/Fake');
