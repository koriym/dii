<?php

use Koriym\Dii\MyYiiBase;

assert(class_exists(YiiBase::class));
spl_autoload_unregister([YiiBase::class,'autoload']);
spl_autoload_register([MyYiiBase::class, 'autoload']);
