<?php

namespace Ray\Dyii\Module;

use Ray\Di\AbstractModule;
use Vendor\Hello\Foo;
use Vendor\Hello\FooInterface;

class AppModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(\SiteController::class);
        $this->bind(FooInterface::class)->to(Foo::class);
    }
}
