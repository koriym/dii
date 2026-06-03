<?php

declare(strict_types=1);

namespace Koriym\Dii\Exception;

use LogicException;

/**
 * Thrown when an injectable is created before Dii::setContext() is called
 */
final class ContextNotSet extends LogicException
{
}
