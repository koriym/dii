<?php

namespace Koriym\Dii\Module;

use Ray\Di\AbstractModule;
use Vendor\Hello\FakeFoo;
use Vendor\Hello\FooInterface;

class TestModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FooInterface::class)->to(FakeFoo::class);
    }
}
