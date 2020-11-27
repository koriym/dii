<?php

namespace Koriym\Dii;

class DiiConsoleApplication extends \CConsoleApplication
{
    protected function createCommandRunner()
    {
        return new DiiConsoleCommandRunner();
    }
}
