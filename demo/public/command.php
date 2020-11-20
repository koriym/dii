<?php

// include Yii bootstrap file
require dirname(__DIR__) . '/vendor/autoload.php';
spl_autoload_unregister([YiiBase::class, 'autoload']);

$config = __DIR__ . '/protected/config/main.php';

// create a Console application instance and run
\Koriym\Dii\Dii::createConsoleApplication($config)->run();
