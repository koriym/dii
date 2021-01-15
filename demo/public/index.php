<?php

declare(strict_types=1);

// include Yii bootstrap file
use Composer\Autoload\ClassLoader;
use Koriym\Dii\Dii;
use Koriym\Dii\Test;

$loader = require dirname(__DIR__, 2) . '/vendor/autoload.php';
assert($loader instanceof ClassLoader);
$loader->addPsr4('Vendor\\Hello\\', dirname(__DIR__) . '/src');
$loader->addPsr4('Koriym\\Dii\\', dirname(__DIR__) . '/src-module');

$config = __DIR__ . '/protected/config/main.php';

// set context
Dii::setContext(Test::class);

// create a Web application instance and run
Dii::createWebApplication($config)->run();
