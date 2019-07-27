<?php

namespace Ray\Dyii;

final class Foo implements FooInterface
{
    public function get() : string
    {
        return 'foo';
    }
}
