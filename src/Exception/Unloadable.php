<?php

declare(strict_types=1);

namespace Koriym\Dii\Exception;

use LogicException;

/**
 * Thrown when a requested class cannot be loaded
 */
final class Unloadable extends LogicException
{
}
