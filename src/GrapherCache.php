<?php

declare(strict_types=1);

namespace Koriym\Dii;

use Ray\Di\Grapher;
use Throwable;

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

        try {
            $grapher = is_string($serialized)
                ? @unserialize($serialized, ['allowed_classes' => true])
                : null;
        } catch (Throwable) {
            // A malformed payload's __unserialize()/__wakeup() threw instead
            // of just failing to parse: treat it the same as any other
            // corrupted or tampered cache payload below.
            $grapher = null;
        }

        if ($grapher instanceof Grapher) {
            return $grapher;
        }

        // Corrupted or tampered cache payload: rebuild rather than trust it.
        return $build();
    }
}
