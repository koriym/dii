<?php

declare(strict_types=1);

// include Yii bootstrap file

use Koriym\Dii\Dii;

require dirname(__DIR__) . '/vendor/autoload.php';
spl_autoload_unregister([YiiBase::class, 'autoload']);

$config = __DIR__ . '/protected/config/main.php';

// create a Console application instance and run
Dii::createConsoleApplication($config)->run();
