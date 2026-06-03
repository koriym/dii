<?php

declare(strict_types=1);

namespace Koriym\Dii;

use CException;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Koriym\Dii\Exception\Unloadable;
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
use function method_exists;
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
    public static function setContext(string $contextClass, ?CacheInterface $cache = null, ?string $tmpDir = null): void
    {
        if (! class_exists($contextClass)) {
            throw new Unloadable("Not found context class: {$contextClass}");
        }

        $tmpDir ??= dirname((new ReflectionClass($contextClass))->getFileName()) . '/tmp';
        $cache ??= new FileCache($tmpDir);
        self::$grapher = (new GrapherCache($cache))->get(
            $contextClass,
            static fn (): Grapher => new Grapher((new $contextClass())(), $tmpDir),
        );
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

        $object = self::newInstance($type, $args);
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
     * Register silent annotation loader
     *
     * No-op on attribute-based ray/di (>= 2.16) where doctrine/annotations is absent.
     */
    public static function registerAnnotationLoader(): void
    {
        // registerLoader() exists only in doctrine/annotations 1.x; 2.x removed it
        // and attribute-based ray/di (>= 2.16) has no doctrine/annotations at all.
        /** @psalm-suppress UndefinedClass */
        if (! method_exists(AnnotationRegistry::class, 'registerLoader')) {
            return;
        }

        /** @psalm-suppress UndefinedClass */
        AnnotationRegistry::reset();
        /** @psalm-suppress UndefinedClass, UndefinedMethod */
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

    /**
     * Create an instance through the Grapher when injectable, otherwise plainly
     *
     * @param array<mixed> $args
     *
     * @throws ReflectionException
     */
    private static function newInstance(string $type, array $args): object
    {
        $isInjectable = in_array(Injectable::class, class_implements($type), true);
        if ($isInjectable && self::$grapher instanceof Grapher) {
            /** @var object $object */
            $object = self::$grapher->newInstanceArgs($type, $args);

            return $object;
        }

        return (new ReflectionClass($type))->newInstanceArgs($args);
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
}
