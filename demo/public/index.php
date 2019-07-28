<?php

// include Yii bootstrap file
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/koriym/dyii/yii_ray.php';
spl_autoload_unregister([YiiBase::class, 'autoload']);

$config = __DIR__ . '/protected/config/main.php';

// create a Web application instance and run
Yii::createWebApplication($config)->run();
