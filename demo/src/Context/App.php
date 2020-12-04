<?php

namespace Koriym\Dii\Context;

use Koriym\Dii\Module\AppModule;
use Koriym\Dii\ModuleProvider;
use Ray\Di\AbstractModule;

class App implements ModuleProvider
{
    public function __invoke() : AbstractModule
    {
        return new AppModule();
    }
}
