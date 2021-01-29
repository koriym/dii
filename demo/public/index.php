<?php

declare(strict_types=1);

// include Yii bootstrap file
use Koriym\Dii\Dii;
use Koriym\Dii\Test;

$loader = require dirname(__DIR__) . '/vendor/autoload.php';
Dii::registerAnnotationLoader(); // アノテーションローダーのwarning抑制
//Dii::registerSilentAutoLoader(); // YiiBase::autoloadのwarning抑制

$config = __DIR__ . '/protected/config/main.php';

// set context
Dii::setContext(Test::class);

// create a Web application instance and run
Yii::createWebApplication($config)->run();
