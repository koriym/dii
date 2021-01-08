<?php

namespace Koriym\Dii\Module;

use Koriym\Dii\FooInterface;
use Koriym\Dii\TestFoo;
use Ray\Di\AbstractModule;

class TestModule extends AbstractModule
{
    protected function configure()
    {
        // binding for the test
        $this->bind(FooInterface::class)->to(TestFoo::class);
    }
}
