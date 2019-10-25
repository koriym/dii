<?php

namespace Koriym\Dii;

use CException;
use Ray\Di\Grapher;
use Koriym\Dii\Module\AppModule;
use YiiBase;

/**
 * Ray.Di powered Yii class
 */
class Dii extends YiiBase
{
    private static $_imports=array();					// alias => class name or directory

    /**
     * {@inheritdoc}
     *
     * @throws \ReflectionException
     */
    public static function createComponent($config)
    {
        $args = func_get_args();
        [$type, $config] = self::extract($config);
        unset($args[0]);
        $hasDot = is_int(strpos($type, '.'));
        if ($hasDot) {
//            parent::createComponent($config);
            throw new \LogicException($type);
        }
        $isInjectable = in_array(Injectable::class, class_implements($type), true);
        $object = $isInjectable ? Dii::getGrapher()->newInstanceArgs($type, $args) : (new \ReflectionClass($type))->newInstanceArgs($args);

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
        return self::createApplication(DiiWebApplication::class, $config);
    }

    public static function getGrapher() : Grapher
    {
        $tmpDir = dirname((new \ReflectionClass(AppModule::class))->getFileName()) . '/tmp';

        return new Grapher(new AppModule, $tmpDir);
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

    public static function import($alias,$forceInclude=false)
    {
//        class_exists($alias, true);
//        self::$_imports[$alias]=$alias;

        if(isset(self::$_imports[$alias]))  // previously imported
            return self::$_imports[$alias];

        if(class_exists($alias,false) || interface_exists($alias,false))
            return self::$_imports[$alias]=$alias;

        if(($pos=strrpos($alias,'\\'))!==false) // a class name in PHP 5.3 namespace format
        {
            $namespace=str_replace('\\','.',ltrim(substr($alias,0,$pos),'\\'));
            if(($path=self::getPathOfAlias($namespace))!==false)
            {
                $classFile=$path.DIRECTORY_SEPARATOR.substr($alias,$pos+1).'.php';
                if($forceInclude)
                {
                    if(is_file($classFile))
                        require($classFile);
                    else
                        throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing PHP file and the file is readable.',array('{alias}'=>$alias)));
                    self::$_imports[$alias]=$alias;
                }
                else
                    self::$classMap[$alias]=$classFile;
                return $alias;
            }
            else
            {
                // try to autoload the class with an autoloader
                if (class_exists($alias,true))
                    return self::$_imports[$alias]=$alias;
                else
                    throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing directory or file.',
                        array('{alias}'=>$namespace)));
            }
        }

        if(($pos=strrpos($alias,'.'))===false)  // a simple class name
        {
            // try to autoload the class with an autoloader if $forceInclude is true
            if($forceInclude && (self::autoload($alias,true) || class_exists($alias,true)))
                self::$_imports[$alias]=$alias;
            return $alias;
        }

        $className=(string)substr($alias,$pos+1);
        $isClass=$className!=='*';

        if($isClass && (class_exists($className,false) || interface_exists($className,false)))
            return self::$_imports[$alias]=$className;

        if(($path=self::getPathOfAlias($alias))!==false)
        {
            if($isClass)
            {
                if($forceInclude)
                {
                    if(is_file($path.'.php'))
                        require($path.'.php');
                    else
                        throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing PHP file and the file is readable.',array('{alias}'=>$alias)));
                    self::$_imports[$alias]=$className;
                }
                else
                    self::$classMap[$className]=$path.'.php';
                return $className;
            }
            else  // a directory
            {
                if(self::$_includePaths===null)
                {
                    self::$_includePaths=array_unique(explode(PATH_SEPARATOR,get_include_path()));
                    if(($pos=array_search('.',self::$_includePaths,true))!==false)
                        unset(self::$_includePaths[$pos]);
                }

                array_unshift(self::$_includePaths,$path);

                if(self::$enableIncludePath && set_include_path('.'.PATH_SEPARATOR.implode(PATH_SEPARATOR,self::$_includePaths))===false)
                    self::$enableIncludePath=false;

                return self::$_imports[$alias]=$path;
            }
        }
        else
            throw new CException(Yii::t('yii','Alias "{alias}" is invalid. Make sure it points to an existing directory or file.',
                array('{alias}'=>$alias)));
    }
}
