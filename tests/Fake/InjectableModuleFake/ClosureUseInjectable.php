<?php

declare(strict_types=1);

namespace Koriym\Dii\InjectableModuleFake;

use Koriym\Dii\Injectable;

// A closure `use (...)` capture clause at file scope, before any real brace
// has been pushed onto the scope stack, must not be misread as a
// namespace-level import. If it were, the tokenizer would try to parse the
// capture list/body as import names, and the bare class-constant fetch below
// would overwrite the `Injectable` alias imported above with this
// (never loaded, never called) decoy FQCN.
$closureUseCaptured = 'captured';
$closureUseDecoy = static function () use ($closureUseCaptured) {
    \Koriym\Dii\InjectableModuleFakeClosureDecoy\Injectable::class;

    return $closureUseCaptured;
};

class ClosureUseInjectable implements Injectable
{
}
