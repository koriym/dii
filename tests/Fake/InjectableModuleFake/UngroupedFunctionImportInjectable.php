<?php

declare(strict_types=1);

namespace Koriym\Dii\InjectableModuleFakeUngroupedDecoy;

function Injectable(): void
{
}

namespace Koriym\Dii\InjectableModuleFake;

use Koriym\Dii\Injectable;
use function strlen, Koriym\Dii\InjectableModuleFakeUngroupedDecoy\Injectable;

// `use function A, B;` (no group braces) applies the `function` modifier to
// every comma-separated item, so neither `strlen` nor this decoy
// `...UngroupedDecoy\Injectable` function import may shadow the class alias
// imported above.
class UngroupedFunctionImportInjectable implements Injectable
{
}
