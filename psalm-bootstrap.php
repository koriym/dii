<?php

declare(strict_types=1);

use Koriym\Dii\Dii;

require __DIR__ . '/vendor/autoload.php';

// Load YiiBase (this registers Yii's own autoloader), then silence that
// autoloader so Psalm does not crash when Yii probes optional classes
// (e.g. HTMLPurifier) via class_exists() during static analysis.
class_exists('YiiBase');
Dii::registerSilentAutoLoader();
