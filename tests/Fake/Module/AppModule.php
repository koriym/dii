<?php

namespace Koriym\Dii\Module;

use Ray\Di\AbstractModule;
use Koriym\Dii\BarInterceptor;
use Koriym\Dii\FakeSiteController;
use Koriym\Dii\Foo;
use Koriym\Dii\FooInterface;
use function var_dump;

class AppModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FooInterface::class)->to(Foo::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->startsWith('actionIndex'),
            [BarInterceptor::class]
        );
        $this->bind(FakeSiteController::class);
    }
}
