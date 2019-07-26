<?php

return function ($className,$classMapOnly=false)
{
    foreach (YiiBase::$autoloaderFilters as $filter)
    {
        if (is_array($filter)
            && isset($filter[0]) && isset($filter[1])
            && is_string($filter[0]) && is_string($filter[1])
            && true === call_user_func(array($filter[0], $filter[1]), $className)
        )
        {
            return true;
        }
        elseif (is_string($filter)
            && true === call_user_func($filter, $className)
        )
        {
            return true;
        }
        elseif (is_callable($filter)
            && true === $filter($className)
        )
        {
            return true;
        }
    }

    // use include so that the error PHP file may appear
    if(isset(YiiBase::$classMap[$className]))
        include(YiiBase::$classMap[$className]);
    elseif(isset(YiiBase::$_coreClasses[$className]))
        include(YII_PATH.YiiBase::$_coreClasses[$className]);
    elseif($classMapOnly)
        return false;
    else
    {
        // include class file relying on include_path
        if(strpos($className,'\\')===false)  // class without namespace
        {
            if(YiiBase::$enableIncludePath===false)
            {
                foreach(YiiBase::$_includePaths as $path)
                {
                    $classFile=$path.DIRECTORY_SEPARATOR.$className.'.php';
                    if(is_file($classFile))
                    {
                        include($classFile);
                        if(YII_DEBUG && basename(realpath($classFile))!==$className.'.php')
                            throw new CException(Yii::t('yii','Class name "{class}" does not match class file "{file}".', array(
                                '{class}'=>$className,
                                '{file}'=>$classFile,
                            )));
                        break;
                    }
                }
            }
            else
                $er = error_reporting(0);
                include($className . '.php');
                ini_set('error_reporting', $er);
        }
        else  // class name with namespace in PHP 5.3
        {
            $namespace=str_replace('\\','.',ltrim($className,'\\'));
            if(($path=YiiBase::getPathOfAlias($namespace))!==false && is_file($path.'.php'))
                include($path.'.php');
            else
                return false;
        }
        return class_exists($className,false) || interface_exists($className,false);
    }
    return true;
};
