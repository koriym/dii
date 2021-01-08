<?php

declare(strict_types=1);

namespace Vendor\Hello;

final class FakeFoo implements FooInterface
{
    public function get(): string
    {
        return ' +injected(fake)';
    }
}
