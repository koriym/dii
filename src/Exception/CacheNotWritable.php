<?php

declare(strict_types=1);

namespace Koriym\Dii\Exception;

use RuntimeException;

/**
 * Thrown when the file cache directory or file can not be written
 */
final class CacheNotWritable extends RuntimeException
{
}
