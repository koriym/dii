<?php

declare(strict_types=1);

namespace Koriym\Dii;

use RuntimeException;

/** Test double whose __wakeup() always throws. */
final class ThrowsOnWakeup
{
    public function __wakeup()
    {
        throw new RuntimeException('wakeup exploded');
    }
}
