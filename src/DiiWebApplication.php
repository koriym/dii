<?php

namespace Koriym\Dii;

class DiiWebApplication extends \CWebApplication
{
    /** @var ControllerClassResolver */
    private $controllerClassResolver;

    /**
     * @inheritDoc
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
        $this->controllerClassResolver = new ControllerClassResolver();
    }

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

                $controllerClass = ($this->controllerClassResolver)($className, $controllerID, $id, $owner === $this ? null : $owner);
                if ($controllerClass === null) {
                    return null;
                }
                return [$controllerClass, $this->parseActionParams($route)];
            }
            $controllerID .= $id;
            $basePath .= DIRECTORY_SEPARATOR . $id;
        }
        return null;
    }
}
