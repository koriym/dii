<?php

declare(strict_types=1);

namespace Koriym\Dii\InjectableModuleFakeDecoy;

trait Injectable
{
}

namespace Koriym\Dii\InjectableModuleFake;

use Koriym\Dii\Injectable;

trait TraitFirstSupportTrait
{
    // A trait-use as the first statement in a trait/class body is immediately
    // preceded by the enclosing body's own opening brace, which used to be
    // (mis)recognized as a namespace-level `use` import. That would overwrite
    // the `Injectable` alias imported above with this decoy trait's FQCN,
    // breaking the `implements Injectable` resolution on the class below.
    use \Koriym\Dii\InjectableModuleFakeDecoy\Injectable;
}

class TraitFirstInjectable implements Injectable
{
    use TraitFirstSupportTrait;
}
