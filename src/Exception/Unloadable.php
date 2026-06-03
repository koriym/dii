<?php

declare(strict_types=1);

namespace Koriym\Dii\Exception;

use LogicException;

/**
 * Thrown when the context class can not be loaded
 */
final class Unloadable extends LogicException
{
}
