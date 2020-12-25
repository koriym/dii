<?php

declare(strict_types=1);

namespace Koriym\Dii;

use Ray\Di\AbstractModule;

interface ModuleProvider
{
    public function __invoke(): AbstractModule;
}
