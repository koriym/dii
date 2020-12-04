<?php

namespace Koriym\Dii\Context;

use Koriym\Dii\Module\AppModule;
use Koriym\Dii\Module\TestModule;
use Koriym\Dii\ModuleProvider;
use Ray\Di\AbstractModule;

class Test implements ModuleProvider
{
    public function __invoke() : AbstractModule
    {
        return new TestModule(new AppModule());
    }
}
