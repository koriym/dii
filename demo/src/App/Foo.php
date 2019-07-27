<?php

namespace Vendor\Hello;

final class Foo implements FooInterface
{
    public function get() : string
    {
        return 'foo';
    }
}
