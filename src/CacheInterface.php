<?php

declare(strict_types=1);

namespace Koriym\Dii;

interface CacheInterface
{
    /**
     * Fetches a value from the pool or computes it if not found.
     */
    public function get(string $key, callable $callback): mixed;
}
