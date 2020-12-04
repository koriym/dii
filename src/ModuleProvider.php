<?php

namespace Koriym\Dii;

use Ray\Di\AbstractModule;

interface ModuleProvider
{
    public function __invoke() : AbstractModule;
}
