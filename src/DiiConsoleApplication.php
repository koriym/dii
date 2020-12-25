<?php

declare(strict_types=1);

namespace Koriym\Dii;

use CConsoleApplication;

class DiiConsoleApplication extends CConsoleApplication
{
    /**
     * @return DiiConsoleCommandRunner
     */
    protected function createCommandRunner()
    {
        return new DiiConsoleCommandRunner();
    }
}
