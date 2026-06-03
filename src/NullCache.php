<?php

declare(strict_types=1);

namespace Koriym\Dii;

/**
 * Cache that never stores; always recomputes
 *
 * Pass this to Dii::setContext() during development so module changes take
 * effect immediately without clearing the file cache.
 */
final class NullCache implements CacheInterface
{
    public function get(string $key, callable $callback): mixed
    {
        return $callback();
    }
}
