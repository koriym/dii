<?php

namespace Koriym\Dii;

use Koriym\Dii\Module\AppModule;
use Ray\Di\AbstractModule;

class App implements ModuleProvider
{
    public function __invoke() : AbstractModule
    {
        return new AppModule();
    }
}
