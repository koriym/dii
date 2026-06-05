<?php

declare(strict_types=1);

namespace Koriym\Dii\InjectableModuleFake;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

class AopInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation)
    {
        $target = $invocation->getThis();
        if ($target instanceof AopScannedInjectable) {
            $target->intercepted = true;
        }

        return $invocation->proceed();
    }
}
