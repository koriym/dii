<?php

namespace Vendor\App\Command;

use Ray\Di\Di\Inject;
use Koriym\Dii\Injectable;
use Vendor\Hello\FooInterface;

class NamespacedCommand extends \CConsoleCommand implements Injectable
{
    /**
     * @var FooInterface
     */
    private $foo;

    /**
     * @Inject
     */
    public function setFoo(FooInterface $foo) : void
    {
        $this->foo = $foo;
    }

    public function actionIndex() : void
    {
        echo  $this->foo->get() . PHP_EOL;
        echo ' This is namespaced command.' . PHP_EOL;
    }
}
