<?php

// include Yii bootstrap file
use Koriym\Dii\Context\Test;
use Koriym\Dii\Dii;

require dirname(__DIR__) . '/vendor/autoload.php';
spl_autoload_unregister([YiiBase::class, 'autoload']);

$config = __DIR__ . '/protected/config/main.php';

// set context
Dii::setContext(Test::class);

// create a Web application instance and run
Yii::createWebApplication($config)->run();
