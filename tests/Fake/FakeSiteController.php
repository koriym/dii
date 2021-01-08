<?php

namespace Koriym\Dii;

use CController;
use Koriym\Dii\Injectable;
use Ray\Di\Di\Inject;

/**
 * SiteController is the default controller to handle user requests.
 */
class FakeSiteController extends CController implements Injectable
{
    /** @var FooInterface */
    public $foo;

    /** @var bool  */
    public $intercepted = false;

    /**
     * @Inject
     */
    public function setFoo(FooInterface $foo)
    {
        $this->foo = $foo;
    }

    /**
     * Index action is the default action in a controller.
     */
    public function actionIndex()
    {
        return '';
    }
}
