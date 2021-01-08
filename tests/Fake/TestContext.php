<?php

namespace Koriym\Dii;

use Koriym\Dii\Module\AppModule;
use Koriym\Dii\Module\TestModule;
use Koriym\Dii\ModuleProvider;
use Ray\Di\AbstractModule;

class TestContext implements ModuleProvider
{
    public function __invoke() : AbstractModule
    {
        // override AppModule with TestModule
        return new TestModule(new AppModule());
    }
}
