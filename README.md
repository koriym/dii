# Dii

## Dependency Injection Container Plugin for Yii 1

This plugin adds the ability to configure object instances and their dependencies before they are used, and to store them into a container class to easy access.

It uses the clean and flexible [Ray.Di](https://github.com/ray-di/Ray.Di) DI framework which is a PHP dependency injection framework in the style of "Google Guice".

Ray.Di also allows you to program using AOP, that is, decorating the configured instances so some logic can be run before or after any of their methods.

## Configuration

### Bootstrap file

Use composer autoloader instead of Yii autoloader.

```php
// composer autoloader
require dirname(__DIR__) . '/vendor/autoload.php';
spl_autoload_unregister([YiiBase::class, 'autoload']);

// set context module
Dii::setContext(Test::class);

// run the application
Yii::createWebApplication()->run();
```

### Binding module

Modules are classes that describe how instances and their dependencies should be constructed, they provide a natural way of grouping configurations. An example module looks like this:

```php
<?php

namespace Koriym\Dii\Module;

use Ray\Di\AbstractModule;
use Vendor\Hello\BarInterceptor;
use Vendor\Hello\Foo;
use Vendor\Hello\FooInterface;

class AppModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(\SiteController::class);
        $this->bind(FooInterface::class)->to(Foo::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->startsWith('actionIndex'),
            [BarInterceptor::class]
        );
    }
}
```

Note: Module name is fixed as `Koriym\Dii\Module\AppModule`.

## Injecting Dependencies in Controllers

Ray.Di is able to inject instances to your controllers based on annotations:

```php
<?php

use Koriym\Dii\Injectable;
use Ray\Di\Di\Inject;
use Vendor\Hello\FooInterface;

class SiteController extends CController implements Injectable
{
    private $foo;

    /**
     * @Inject
     */
    public function setDeps(FooInterface $foo)
    {
        $this->foo = $foo;
    }

    public function actionIndex()
    {
        echo 'Hello World' . $this->foo->get();
    }
}
```

As soon as the controller is created, all methods having the `@Inject` annotation will get instances of the hinted class passed. This works only for setter method, not constructors. Please implemet marker interface `Injectable` to notify Ray.Di the class injectable.

Also any class created by `Yii:createComponent()` method is worked as well.

## Demo

    cd demo
    composer install
    composer serve
