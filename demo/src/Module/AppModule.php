<?php

namespace Koriym\Dii\Module;

use Ray\Di\AbstractModule;
use Vendor\Hello\BarInterceptor;
use Vendor\Hello\Foo;
use Vendor\Hello\FooInterface;
use Vendor\App\Command\NamespacedCommand;

class AppModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(\SiteController::class);
        $this->bind(\SampleCommand::class);
        $this->bind(NamespacedCommand::class);
        $this->bind(FooInterface::class)->to(Foo::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->startsWith('actionIndex'),
            [BarInterceptor::class]
        );
    }
}
