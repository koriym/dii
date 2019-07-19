<?php
/**
 * Yii bootstrap file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @package system
 * @since 1.0
 */

require __DIR__ . '/vendor/autoload.php';

if(!class_exists('YiiBase', false))
	require(__DIR__ . '/vendor/yiisoft/yii/framework/YiiBase.php');

/**
 * Yii is a helper class serving common framework functionalities.
 *
 * It encapsulates {@link YiiBase} which provides the actual implementation.
 * By writing your own Yii class, you can customize some functionalities of YiiBase.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system
 * @since 1.0
 */
class Yii extends YiiBase
{
    /**
     * {@inheritdoc}
     */
    public static function createComponent($config)
    {
        $args = func_get_args();
        if(is_string($config)) {
            $type=$config;
            $config=array();
        } elseif(isset($config['class'])) {
            $type=$config['class'];
            unset($config['class']);
        } else {
            throw new CException(Yii::t('yii', 'Object configuration must be an array containing a "class" element.'));
        }
        if(!class_exists($type,false)) {
            $type = Yii::import( $type, true );
        }

        unset($args[0]);
        $class=new ReflectionClass($type);
        $object=$class->newInstanceArgs($args);
        foreach($config as $key=>$value) {
            $object->$key = $value;
        }

        return $object;
    }
}
