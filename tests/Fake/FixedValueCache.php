<?php

declare(strict_types=1);

namespace Koriym\Dii;

/** Test double: always returns a fixed value, ignoring the build callback. */
final class FixedValueCache implements CacheInterface
{
    public function __construct(private mixed $value)
    {
    }

    public function get(string $key, callable $callback): mixed
    {
        return $this->value;
    }
}
