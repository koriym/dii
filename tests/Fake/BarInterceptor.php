<?php

namespace Koriym\Dii;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

class BarInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation)
    {
        $target = $invocation->getThis();
        if ($target instanceof  FakeSiteController) {
            $target->intercepted = true;
        }
        return $invocation->proceed();
    }
}
