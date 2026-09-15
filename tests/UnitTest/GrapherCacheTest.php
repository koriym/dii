<?php

declare(strict_types=1);

namespace Koriym\Dii;

use PHPUnit\Framework\TestCase;
use Ray\Di\Grapher;

use function serialize;
use function sys_get_temp_dir;

final class GrapherCacheTest extends TestCase
{
    public function testValueIsMemoizedAcrossCalls(): void
    {
        $cache = new SingleValueCache();

        $calls = 0;
        $build = static function () use (&$calls) {
            $calls++;

            return new Grapher(new Module\AppModule(), sys_get_temp_dir());
        };

        $first = (new GrapherCache($cache))->get('ctx', $build);
        $second = (new GrapherCache($cache))->get('ctx', $build);

        $this->assertInstanceOf(Grapher::class, $first);
        $this->assertInstanceOf(Grapher::class, $second);
        $this->assertSame(1, $calls);
    }

    public function testCorruptedPayloadRebuildsRatherThanFail(): void
    {
        $cache = new FixedValueCache('not a valid serialized payload');

        $calls = 0;
        $grapher = (new GrapherCache($cache))->get('ctx', static function () use (&$calls) {
            $calls++;

            return new Grapher(new Module\AppModule(), sys_get_temp_dir());
        });

        $this->assertInstanceOf(Grapher::class, $grapher);
        $this->assertSame(1, $calls);
    }

    public function testThrowingWakeupRebuildsRatherThanPropagate(): void
    {
        $cache = new FixedValueCache(serialize(new ThrowsOnWakeup()));

        $calls = 0;
        $grapher = (new GrapherCache($cache))->get('ctx', static function () use (&$calls) {
            $calls++;

            return new Grapher(new Module\AppModule(), sys_get_temp_dir());
        });

        $this->assertInstanceOf(Grapher::class, $grapher);
        $this->assertSame(1, $calls);
    }
}
