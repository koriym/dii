<?php

declare(strict_types=1);

namespace Vendor\Hello;

final class Foo implements FooInterface
{
    public function get(): string
    {
        return ' +injected';
    }
}
