<?php

use Ray\Dyii\FooInterface;
use Ray\Dyii\Injectable;
use Ray\Di\Di\Inject;

/**
 * SiteController is the default controller to handle user requests.
 */
class SiteController extends CController implements Injectable
{
    /**
     * @var FooInterface
     */
    private $foo;

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
		echo 'Hello World' . $this->foo->get();
	}
}
