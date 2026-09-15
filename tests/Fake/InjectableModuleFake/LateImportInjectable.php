<?php

declare(strict_types=1);

namespace Koriym\Dii\InjectableModuleFake;

use Koriym\Dii\Injectable;

class FirstBeforeLateImport implements Injectable
{
}

use Koriym\Dii\Injectable as LateInjectable;

class LateImportInjectable implements LateInjectable
{
}
