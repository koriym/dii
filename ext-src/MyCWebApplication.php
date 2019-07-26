<?php

use Ray\Di\Injector;
use Ray\Dyii\AppModule;
use Ray\Dyii\Injectable;

class MyCWebApplication extends CWebApplication
{
    /**
     * {@inheritDoc}
     */
	public function createController($route,$owner=null)
	{
		if($owner===null)
			$owner=$this;
		if((array)$route===$route || ($route=trim($route,'/'))==='')
			$route=$owner->defaultController;
		$caseSensitive=$this->getUrlManager()->caseSensitive;

		$route.='/';
		while(($pos=strpos($route,'/'))!==false)
		{
			$id=substr($route,0,$pos);
			if(!preg_match('/^\w+$/',$id))
				return null;
			if(!$caseSensitive)
				$id=strtolower($id);
			$route=(string)substr($route,$pos+1);
			if(!isset($basePath))  // first segment
			{
				if(isset($owner->controllerMap[$id]))
				{
					return array(
						Yii::createComponent($owner->controllerMap[$id],$id,$owner===$this?null:$owner),
						$this->parseActionParams($route),
					);
				}

				if(($module=$owner->getModule($id))!==null)
					return $this->createController($route,$module);

				$basePath=$owner->getControllerPath();
				$controllerID='';
			}
			else
				$controllerID.='/';
			$className=ucfirst($id).'Controller';
			$classFile=$basePath.DIRECTORY_SEPARATOR.$className.'.php';

			if($owner->controllerNamespace!==null)
				$className=$owner->controllerNamespace.'\\'.str_replace('/','\\',$controllerID).$className;

			if(is_file($classFile))
			{
				if(!class_exists($className,false))
					require($classFile);
				if(class_exists($className,false) && is_subclass_of($className,'CController'))
				{
					$id[0]=strtolower($id[0]);
					return array(
					    $this->newInstance($className, $controllerID.$id,$owner===$this?null:$owner),
						$this->parseActionParams($route),
					);
				}
				return null;
			}
			$controllerID.=$id;
			$basePath.=DIRECTORY_SEPARATOR.$id;
		}
	}

	private function newInstance(string $className, $argument)
    {
        $isInjectable = in_array(Injectable::class, class_implements($className));
        $controller = $isInjectable ? (new Injector(new AppModule()))->getInstanceWithArgs($className, '', $argument) : new $className($argument);

        return $controller;
    }
}
