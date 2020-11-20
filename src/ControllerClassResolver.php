<?php

declare(strict_types=1);

namespace Koriym\Dii;

use CController;

/**
 * コントローラー名からクラスを探索してインスタンス化する
 */
class ControllerClassResolver
{
    /**
     * @param string $className
     * @param string $controllerID
     * @param string $id
     * @param mixed $owner
     * @return CController|null
     */
    public function __invoke(string $className, string $controllerID, string $id, $owner): ?CController
    {
        if (class_exists($className, false) && is_subclass_of($className, CController::class)) {
            return $this->newInstance($className, $controllerID, $id, $owner);
        }

        $namespacedClassName = 'application\\' . ucfirst($id) . 'Controller';
        if (class_exists($namespacedClassName, false) && is_subclass_of($namespacedClassName, CController::class)) {
            return $this->newInstance($namespacedClassName, $controllerID, $id, $owner);
        }

        return null;
    }

    /**
     * @param string $className
     * @param string $controllerId
     * @param string $id
     * @param mixed $owner
     * @return CController
     */
    private function newInstance(string $className, string $controllerId, string $id, $owner): CController
    {
        $id[0] = strtolower($id[0]);
        $controllerId .= $id;
        $isInjectable = in_array(Injectable::class, class_implements($className), true);
        $controller = $isInjectable ? Dii::getGrapher()->newInstanceArgs($className, [$controllerId, $owner]) : new $className($controllerId, $owner);

        return $controller;
    }
}
