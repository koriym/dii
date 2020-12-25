<?php

declare(strict_types=1);

namespace Koriym\Dii;

use CController;
use CException;
use CWebApplication;
use ReflectionException;

use function class_exists;
use function is_file;
use function is_subclass_of;
use function preg_match;
use function str_replace;
use function strpos;
use function strtolower;
use function substr;
use function trim;
use function ucfirst;

use const DIRECTORY_SEPARATOR;

class DiiWebApplication extends CWebApplication
{
    /** @var array<string, string|array<string, mixed>> */
    public $commandMap = [];

    /**
     * {@inheritdoc}
     *
     * @throws CException
     * @throws ReflectionException
     *
     * @psalm-suppress UndefinedVariable
     * @psalm-suppress ArgumentTypeCoercion
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

                if (class_exists($className, false) && is_subclass_of($className, CController::class)) {
                    $id[0] = strtolower($id[0]);

                    return [
                        Dii::createComponent($className, $id, $owner === $this ? null : $owner),
                        $this->parseActionParams($route),
                    ];
                }

                $controllerName = ucfirst($id) . 'Controller';

                $namespacedClassName = 'application\\' . $controllerName;
                if (class_exists($namespacedClassName, false) && is_subclass_of($namespacedClassName, CController::class)) {
                    $id[0] = strtolower($id[0]);

                    return [
                        Dii::createComponent($namespacedClassName, $id, $owner === $this ? null : $owner),
                        $this->parseActionParams($route),
                    ];
                }

                return null;
            }

            $controllerID .= $id;
            $basePath .= DIRECTORY_SEPARATOR . $id;
        }
    }
}
