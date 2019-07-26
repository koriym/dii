<?php

use MyVendor\MyProject\AppModule;
use Ray\Di\InjectableInterface;
use Ray\Di\Injector;
use Ray\Di\NullModule;

spl_autoload_unregister(array('YiiBase','autoload'));
require 'vendor/autoload.php';
spl_autoload_register(array('YiiBase','autoload'), true, true);
require __DIR__ . '/ray-src/AppModule.php';
require __DIR__ . '/ray-src/InjectableInterface.php';

if(!class_exists('YiiBase', false)) {
    require(__DIR__ . '/vendor/yiisoft/yii/framework/YiiBase.php');
}

/**
 * Ray.Di powered Yii base class
 */
class Yii extends YiiBase
{
    /**
     * {@inheritdoc}
     */
    public static function createComponent($config)
    {
        $args = func_get_args();
        [$type, $config] = self::extract($config);
        if(!class_exists($type,false)) {
            $type = Yii::import( $type, true );
        }
        unset($args[0]);

        $isInjectable = in_array(InjectableInterface::class, class_implements($type));
        $object = $isInjectable ? (new Injector(new AppModule))->getInstanceWithArgs($type, '', $args) : (new ReflectionClass($type))->newInstanceArgs($args);

        foreach($config as $key=>$value) {
            $object->$key = $value;
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public static function createWebApplication($config=null)
    {
        return self::createApplication('MyCWebApplication',$config);
    }

    /**
     * Extract config
     *
     * @param string|array $config
     *
     * @return [$type, $config]
     * @throws CException
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

        throw new CException(Yii::t( 'yii', 'Object configuration must be an array containing a "class" element.'));
    }
}
