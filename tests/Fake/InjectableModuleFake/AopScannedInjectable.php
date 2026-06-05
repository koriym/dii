<?php

declare(strict_types=1);

namespace Koriym\Dii\InjectableModuleFake;

use Koriym\Dii\Injectable;

class AopScannedInjectable implements Injectable
{
    public bool $intercepted = false;

    public function actionIndex(): string
    {
        return '';
    }
}
