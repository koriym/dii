<?php

namespace {
    use Ray\Aop\WeavedInterface;
    use Ray\Aop\ReflectiveMethodInvocation as Invocation;
    use Koriym\Dii\Injectable;
    use Ray\Di\Di\Inject;
    use Vendor\Hello\FooInterface;
    /**
     * SiteController is the default controller to handle user requests.
     */
    class SiteController_624004720 extends \SiteController implements Injectable, WeavedInterface
    {
        public $bind;
        public $bindings = [];
        public $methodAnnotations = 'a:1:{s:6:"setFoo";a:1:{i:0;O:16:"Ray\\Di\\Di\\Inject":1:{s:8:"optional";b:0;}}}';
        public $classAnnotations = 'a:0:{}';
        private $isAspect = true;
        /**
         * Index action is the default action in a controller.
         */
        public function actionIndex()
        {
            if (!$this->isAspect) {
                $this->isAspect = true;
                return call_user_func_array([$this, 'parent::' . __FUNCTION__], func_get_args());
            }
            $this->isAspect = false;
            $result = (new Invocation($this, __FUNCTION__, func_get_args(), $this->bindings[__FUNCTION__]))->proceed();
            $this->isAspect = true;
            return $result;
        }
    }
}
