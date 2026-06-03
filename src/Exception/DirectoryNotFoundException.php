<?php

declare(strict_types=1);

namespace Koriym\Dii\Exception;

use RuntimeException;

/**
 * Thrown when an injectable scan directory does not exist
 */
final class DirectoryNotFoundException extends RuntimeException
{
}
