<?php

// include Yii bootstrap file
require_once(dirname(__DIR__, 2) . '/yii_ray.php');
$config=dirname(__FILE__).'/protected/config/main.php';

// create a Web application instance and run
Yii::createWebApplication($config)->run();
