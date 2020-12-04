<?php

namespace Koriym\Dii\Module;

use Ray\Di\AbstractModule;
use Vendor\Hello\FakeFoo;
use Vendor\Hello\FooInterface;

class TestModule extends AbstractModule
{
    protected function configure()
    {
        // binding for the test
        $this->bind(FooInterface::class)->to(FakeFoo::class);
    }
}
