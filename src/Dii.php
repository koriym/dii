<?php

declare(strict_types=1);

namespace Koriym\Dii;

use CException;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Koriym\Dii\Module\AppModule;
use LengthException;
use LogicException;
use Ray\Di\Grapher;
use ReflectionClass;
use ReflectionException;
use YiiBase;

use function class_exists;
use function class_implements;
use function dirname;
use function error_reporting;
use function func_get_args;
use function in_array;
use function is_string;
use function spl_autoload_register;
use function spl_autoload_unregister;

use const E_ALL;
use const E_WARNING;

/**
 * Ray.Di powered Yii class
 */
class Dii extends YiiBase
{
    /** @var ?Grapher */
    private static $grapher;

    /**
     * @param class-string<ModuleProvider> $contextClass
     */
    public static function setContext(string $contextClass, ?Cache $cache = null, ?string $tmpDir = null): void
    {
        $cache = $cache ?? new ArrayCache();
        $tmpDir = $tmpDir ?? dirname((new ReflectionClass($contextClass))->getFileName()) . '/tmp';
        if (! class_exists($contextClass)) {
            throw new LogicException("Not found context class: {$contextClass}");
        }

        /** @var ?Grapher $cachedModule */
        self::$grapher = $cache->fetch($contextClass);
        if (! self::$grapher instanceof Grapher) {
            $module = (new $contextClass())();
            self::$grapher = new Grapher($module, $tmpDir);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
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
        if ($isInjectable && self::$grapher instanceof Grapher) {
            $object = self::$grapher->newInstanceArgs($type, $args);
        } else {
            $object = (new ReflectionClass($type))->newInstanceArgs($args);
        }

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

    /**
     * {@inheritdoc}
     */
    public static function createConsoleApplication($config = null)
    {
        return self::createApplication(DiiConsoleApplication::class, $config);
    }

    /**
     * Extract config
     *
     * @param string|array<string, mixed> $config
     *
     * @return array{0: string, 1:array<string, mixed>} [$type, $config]
     *
     * @throws CException
     */
    private static function extract($config): array
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

    /**
     * Register silent annotation loader
     */
    public static function registerAnnotationLoader(): void
    {
        AnnotationRegistry::reset();
        AnnotationRegistry::registerLoader([SilentAutoload::class, 'autoload']);
    }

    /**
     * Silence the Yii autoloader
     *
     * Silence YiiBase::autoload, which gives a warning for non-existent classes.
     */
    public static function registerSilentAutoLoader(): void
    {
        spl_autoload_unregister(['YiiBase', 'autoload']);
        spl_autoload_register(static function (string $class): bool {
            $e = error_reporting(E_ALL & ~E_WARNING);
            $loaded = YiiBase::autoload($class);
            error_reporting($e);

            return $loaded;
        });
    }
}
