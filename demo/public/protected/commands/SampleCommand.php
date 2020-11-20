<?php

use Ray\Di\Di\Inject;
use Koriym\Dii\Injectable;
use Vendor\Hello\FooInterface;

class SampleCommand extends \CConsoleCommand implements Injectable
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
        echo ' This is sample command.' . PHP_EOL;
    }
}
