<?php

declare(strict_types=1);

// include Yii bootstrap file
use Composer\Autoload\ClassLoader;
use Koriym\Dii\Dii;
use Koriym\Dii\Test;

$loader = require dirname(__DIR__) . '/vendor/autoload.php';
spl_autoload_unregister([YiiBase::class, 'autoload']);

$config = __DIR__ . '/protected/config/main.php';

// set context
Dii::setContext(Test::class);

// create a Web application instance and run
Yii::createWebApplication($config)->run();
