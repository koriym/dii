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
Dii::setContext(\YourVendor\YourProject\Context\App::class);

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
        $this->bind(FooInterface::class)->to(Foo::class);
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->startsWith('actionIndex'),
            [BarInterceptor::class]
        );
    }
}
```
## Context

You can make the necessary bindings depending on the context. The context class specifies which module to bind in.

```php
use Koriym\Dii\Module\AppModule;
use Koriym\Dii\ModuleProvider;
use Ray\Di\AbstractModule;

class App implements ModuleProvider
{
    public function __invoke() : AbstractModule
    {
        return new AppModule();
    }
}
```

In this example we have overridden the binding of `AppModule` with `TestModule`.

```php
class Test implements ModuleProvider
{
    public function __invoke() : AbstractModule
    {
        // override AppModule with TestModule
        return new TestModule(new AppModule());
    }
}
```

## Caching

By default `Dii::setContext()` rebuilds the object graph (the Ray.Di `Grapher`) on every call, so changes to your modules always take effect.

```php
// Default: rebuild the object graph on every request
Dii::setContext(App::class);
```

In production, pass `FileCache` to cache the graph and skip the costly rebuild. It stores the graph as a `<?php return '...';` file so OPcache keeps it in memory.

```php
use Koriym\Dii\FileCache;

// Production: cache the object graph on the filesystem
Dii::setContext(App::class, new FileCache(__DIR__ . '/tmp'));
```

To use your own storage (APCu, PSR-16, etc.), implement `CacheInterface`:

```php
use Koriym\Dii\CacheInterface;

final class MyCache implements CacheInterface
{
    public function get(string $key, callable $callback): mixed
    {
        // Return the cached value, or run $callback() and store its result.
    }
}

Dii::setContext(App::class, new MyCache());
```

## Injecting Dependencies in Controllers

Ray.Di is able to inject instances to your controllers based on the `#[Inject]` attribute:

```php
<?php

use Koriym\Dii\Injectable;
use Ray\Di\Di\Inject;
use Vendor\Hello\FooInterface;

class SiteController extends CController implements Injectable
{
    private $foo;

    #[Inject]
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

As soon as the controller is created, all methods with the `#[Inject]` attribute will get instances of the hinted class passed. This works only for setter methods, not constructors. Please implement the marker interface `Injectable` to notify Ray.Di that the class is injectable.

> With older, annotation-based Ray.Di (`< 2.16`), the `@Inject` docblock annotation works as well.

### Bind your injectable controllers

Ray.Di resolves bindings at compile time, so each `Injectable` controller or console command must be **bound in your module**. If it is not bound, Ray.Di raises `Untargeted` and the request fails fast. Dii no longer auto-binds the concrete class at runtime: that silent fallback was not AOP-compiled, so it behaved differently from a module binding and the missing binding went unnoticed (especially at compile time).

```php
class AppModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FooInterface::class)->to(Foo::class);

        // Bind each injectable controller / command (untargeted binding)
        $this->bind(SiteController::class);
    }
}
```

Also any class created by `Yii:createComponent()` method is worked as well.

## InjectableModule

Use `InjectableModule` to bind every `Injectable` class in flat Yii controller or command directories at compile time:

```php
use Koriym\Dii\InjectableModule;
use Ray\Di\AbstractModule;

class AppModule extends AbstractModule
{
    protected function configure()
    {
        $this->bind(FooInterface::class)->to(Foo::class);

        // Install this last so explicit bindings above are preserved.
        $this->install(new InjectableModule([
            __DIR__ . '/../protected/controllers',
            __DIR__ . '/../protected/commands',
        ]));
    }
}
```

The scan is non-recursive and only top-level `*.php` files declaring classes that implement `Koriym\Dii\Injectable` are bound. Pass each subdirectory explicitly when it should be scanned.

## Demo

    cd demo
    composer install
    composer serve
