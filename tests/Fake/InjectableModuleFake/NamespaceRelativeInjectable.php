<?php

declare(strict_types=1);

// Deliberately declared in the real `Koriym\Dii` namespace (not
// `...\InjectableModuleFake`) so that `namespace\Injectable` below resolves,
// per PHP's T_NAME_RELATIVE semantics, to the actual `Koriym\Dii\Injectable`
// marker rather than to a same-named decoy. InjectableModule loads this file
// by path (not via Composer's PSR-4 autoloader), so the namespace/path
// mismatch does not affect the scan.
namespace Koriym\Dii;

class NamespaceRelativeInjectable implements namespace\Injectable
{
}
