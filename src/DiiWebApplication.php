<?php

namespace Koriym\Dii;

class DiiWebApplication extends \CWebApplication
{
    /**
     * {@inheritdoc}
     *
     * @psalm-suppress UndefinedVariable
     * @psalm-suppress ArgumentTypeCoercion
     *
     * @throws \CException
     * @throws \ReflectionException
     */
    public function createController($route, $owner = null)
    {
        if ($owner === null) {
            $owner = $this;
        }
        if ((array) $route === $route || ($route = trim($route, '/')) === '') {
            $route = $owner->defaultController;
        }
        $caseSensitive = $this->getUrlManager()->caseSensitive;

        $route .= '/';
        while (($pos = strpos($route, '/')) !== false) {
            $id = substr($route, 0, $pos);
            if (! preg_match('/^\w+$/', $id)) {
                return null;
            }
            if (! $caseSensitive) {
                $id = strtolower($id);
            }
            $route = (string) substr($route, $pos + 1);
            if (! isset($basePath)) {  // first segment
                if (isset($owner->controllerMap[$id])) {
                    return [
                        Dii::createComponent($owner->controllerMap[$id], $id, $owner === $this ? null : $owner),
                        $this->parseActionParams($route),
                    ];
                }

                if (($module = $owner->getModule($id)) !== null) {
                    return $this->createController($route, $module);
                }
                $basePath = $owner->getControllerPath();
                $controllerID = '';
            } else {
                $controllerID .= '/';
            }
            $className = ucfirst($id) . 'Controller';
            $classFile = $basePath . DIRECTORY_SEPARATOR . $className . '.php';

            if ($owner->controllerNamespace !== null) {
                $className = $owner->controllerNamespace . '\\' . str_replace('/', '\\', $controllerID) . $className;
            }

            if (is_file($classFile)) {
                if (! class_exists($className, false)) {
                    include_once $classFile;
                }

                $controllerName = ucfirst($id) . 'Controller';

                $namespacedClassName = 'application\\' . $controllerName;
                if (class_exists($namespacedClassName, false) && is_subclass_of($namespacedClassName, \CController::class)) {
                    $id[0] = strtolower($id[0]);
                    return [
                        $this->newInstance($namespacedClassName, $controllerID . $id, $owner === $this ? null : $owner),
                        $this->parseActionParams($route),
                    ];
                }

                if (class_exists($controllerName, false) && is_subclass_of($controllerName, \CController::class)) {
                    $id[0] = strtolower($id[0]);
                    return [
                        $this->newInstance($controllerName, $controllerID . $id, $owner === $this ? null : $owner),
                        $this->parseActionParams($route),
                    ];
                }

                return null;
            }
            $controllerID .= $id;
            $basePath .= DIRECTORY_SEPARATOR . $id;
        }
    }

    private function newInstance(string $className, $controllerId, $owner)
    {
        $isInjectable = in_array(Injectable::class, class_implements($className), true);
        $controller = $isInjectable ? Dii::getGrapher()->newInstanceArgs($className, [$controllerId, $owner]) : new $className($controllerId, $owner);

        return $controller;
    }
}
