<?php

namespace Koriym\Dii\Module;

use Ray\Di\AbstractModule;
use Vendor\App\Command\NamespacedCommand;
use Vendor\Hello\BarInterceptor;
use Vendor\Hello\Foo;
use Vendor\Hello\FooInterface;

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
    }
}
