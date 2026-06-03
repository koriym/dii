<?php

declare(strict_types=1);

namespace Koriym\Dii;

use Ray\Di\Grapher;

use function is_string;
use function serialize;
use function unserialize;

/**
 * Cache the serialized Grapher
 *
 * Delegates memoization to a {@see CacheInterface}, serializing the Grapher to
 * a string (the only thing the file cache stores) and restoring it on a hit.
 * The Grapher is serializable ({@see Grapher::__wakeup()}).
 */
final class GrapherCache
{
    public function __construct(private CacheInterface $cache)
    {
    }

    /**
     * @param callable():Grapher $build
     */
    public function get(string $contextClass, callable $build): Grapher
    {
        $serialized = $this->cache->get($contextClass, static fn (): string => serialize($build()));
        $grapher = is_string($serialized)
            ? unserialize($serialized, ['allowed_classes' => true])
            : null;
        if ($grapher instanceof Grapher) {
            return $grapher;
        }

        // Corrupted or tampered cache payload: rebuild rather than trust it.
        return $build();
    }
}
