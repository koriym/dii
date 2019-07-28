<?php

use Ray\Di\Injector;
use Ray\Dyii\Injectable;
use Ray\Dyii\Module\AppModule;
use Ray\Dyii\RayCWebApplication;

/**
 * Ray.Di powered Yii base class
 */
class Yii extends YiiBase
{
    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     */
    public static function createComponent($config)
    {
        $args = func_get_args();
        [$type, $config] = self::extract($config);
        if (! class_exists($type, false)) {
            $type = self::import($type, true);
        }
        unset($args[0]);

        $isInjectable = in_array(Injectable::class, class_implements($type), true);
        $object = $isInjectable ?  \Yii::getInjector()->getInstanceWithArgs($type, '', $args) : (new \ReflectionClass($type))->newInstanceArgs($args);

        foreach ($config as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public static function createWebApplication($config = null)
    {
        return self::createApplication(RayCWebApplication::class, $config);
    }

    public static function getInjector() : Injector
    {
        return new Injector(new AppModule);
    }

    public static function autoload($className, $classMapOnly = false)
    {
        foreach (self::$autoloaderFilters as $filter) {
            if (is_array($filter)
                && isset($filter[0]) && isset($filter[1])
                && is_string($filter[0]) && is_string($filter[1])
                && true === call_user_func([$filter[0], $filter[1]], $className)
            ) {
                return true;
            } elseif (is_string($filter)
                && true === call_user_func($filter, $className)
            ) {
                return true;
            } elseif (is_callable($filter)
                && true === $filter($className)
            ) {
                return true;
            }
        }

        // use include so that the error PHP file may appear
        if (isset(self::$classMap[$className])) {
            include self::$classMap[$className];
        } elseif (isset(self::$_coreClasses[$className])) {
            include YII_PATH . self::$_coreClasses[$className];
        } elseif ($classMapOnly) {
            return false;
        } else {
            // include class file relying on include_path
            if (strpos($className, '\\') === false) {  // class without namespace
                if (self::$enableIncludePath === false) {
                    foreach (self::$_includePaths as $path) {
                        $classFile = $path . DIRECTORY_SEPARATOR . $className . '.php';
                        if (is_file($classFile)) {
                            include $classFile;
                            if (YII_DEBUG && basename(realpath($classFile)) !== $className . '.php') {
                                throw new CException(self::t('yii', 'Class name "{class}" does not match class file "{file}".', [
                                    '{class}' => $className,
                                    '{file}' => $classFile,
                                ]));
                            }
                            break;
                        }
                    }
                } else {
                    include $className . '.php';
                }
            } else {  // class name with namespace in PHP 5.3
                $namespace = str_replace('\\', '.', ltrim($className, '\\'));
                if (($path = self::getPathOfAlias($namespace)) !== false && is_file($path . '.php')) {
                    include $path . '.php';
                } else {
                    return false;
                }
            }

            return class_exists($className, false) || interface_exists($className, false);
        }

        return true;
    }

    /**
     * Extract config
     *
     * @param string|array $config
     *
     * @throws CException
     *
     * @return [$type, $config]
     */
    private static function extract($config) : array
    {
        if (is_string($config)) {
            return [$config, []];
        }
        if (isset($config['class'])) {
            $type = $config['class'];
            unset($config['class']);

            return [$type, $config];
        }

        throw new CException(self::t('yii', 'Object configuration must be an array containing a "class" element.'));
    }
}
