<?php

declare(strict_types=1);

namespace Koriym\Dii\InjectableModuleFake;

use Koriym\Dii\InjectableModuleFake\Support\SideEffectFlag;

SideEffectFlag::$loaded = true;

class SideEffectClass
{
}
