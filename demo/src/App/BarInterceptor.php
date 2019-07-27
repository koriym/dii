<?php

namespace Vendor\Hello;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

class BarInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation)
    {
        $invocation->proceed();
        echo ' +intercepted';
    }
}
