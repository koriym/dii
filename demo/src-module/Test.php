<?php

declare(strict_types=1);

namespace Koriym\Dii;

use Koriym\Dii\Module\AppModule;
use Koriym\Dii\Module\TestModule;
use Ray\Di\AbstractModule;

class Test implements ModuleProvider
{
    public function __invoke(): AbstractModule
    {
        // override AppModule with TestModule
        return new TestModule(new AppModule());
    }
}
