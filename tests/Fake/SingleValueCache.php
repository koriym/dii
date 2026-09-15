<?php

declare(strict_types=1);

namespace Koriym\Dii;

/** Test double: computes and memoizes a value on first access, like a real cache. */
final class SingleValueCache implements CacheInterface
{
    private mixed $value = null;
    private bool $hasValue = false;

    public function get(string $key, callable $callback): mixed
    {
        if (! $this->hasValue) {
            $this->value = $callback();
            $this->hasValue = true;
        }

        return $this->value;
    }
}
