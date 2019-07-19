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
        if(is_string($config))
        {
            $type=$config;
            $config=array();
        }
        elseif(isset($config['class']))
        {
            $type=$config['class'];
            unset($config['class']);
        }
        else
            throw new CException(Yii::t('yii','Object configuration must be an array containing a "class" element.'));

        if(!class_exists($type,false))
            $type=Yii::import($type,true);

        if(($n=func_num_args())>1)
        {
            if($n===2)
                $object=new $type($args[1]);
            elseif($n===3)
                $object=new $type($args[1],$args[2]);
            elseif($n===4)
                $object=new $type($args[1],$args[2],$args[3]);
            else
            {
                unset($args[0]);
                $class=new ReflectionClass($type);
                // Note: ReflectionClass::newInstanceArgs() is available for PHP 5.1.3+
                // $object=$class->newInstanceArgs($args);
                $object=call_user_func_array(array($class,'newInstance'),$args);
            }
        }
        else
            $object=new $type;

        foreach($config as $key=>$value)
            $object->$key=$value;

        return $object;
    }
}
