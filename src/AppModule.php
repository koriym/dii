<?php

namespace Ray\Dyii;

use Ray\Di\AbstractModule;

class AppModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(\SiteController::class);
        $this->bind(FooInterface::class)->to(Foo::class);
    }
}
