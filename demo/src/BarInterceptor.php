<?php

declare(strict_types=1);

namespace Vendor\Hello;

use Koriym\Dii\FakeSiteController;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

use const PHP_EOL;

class BarInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation)
    {
        $target = $invocation->getThis();
        if ($target instanceof  FakeSiteController) {
            $target->intercepted = true;
        }

        $invocation->proceed();
        echo ' +intercepted';
    }
}
