<?php

declare(strict_types=1);

namespace Koriym\Dii\InjectableModuleFakeInterpolationDecoy;

trait Injectable
{
}

namespace Koriym\Dii\InjectableModuleFake;

use Koriym\Dii\Injectable;

class InterpolationHost
{
    public function describe(): string
    {
        $name = 'world';

        return "hello {$name}";
    }

    // "{$expr}" string interpolation tokenizes its opener as T_CURLY_OPEN but
    // its closer as a literal '}'. Without pushing a scope frame for the
    // opener, the scope stack desyncs once the method above closes, making
    // this trait-use look like it is back at namespace scope. That
    // misclassifies it as an import and overwrites the `Injectable` alias
    // below with this decoy trait's FQCN.
    use \Koriym\Dii\InjectableModuleFakeInterpolationDecoy\Injectable;
}

class InterpolationInjectable implements Injectable
{
}
