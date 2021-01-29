<?php

declare(strict_types=1);

namespace Koriym\Dii;

use CException;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Koriym\Dii\Module\AppModule;
use LengthException;
use LogicException;
use Ray\Di\AbstractModule;
use Ray\Di\Bind;
use Ray\Di\Exception\Unbound;
use Ray\Di\Grapher;
use ReflectionClass;
use ReflectionException;
use YiiBase;

use function assert;
use function class_exists;
use function class_implements;
use function dirname;
use function error_reporting;
use function func_get_args;
use function in_array;
use function is_callable;
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
    /** @var class-string<ModuleProvider>  */
    public static $context = App::class;

    /** @var AbstractModule */
    private static $module;

    /**
     * @param class-string<ModuleProvider> $context
     */
    public static function setContext(string $context): void
    {
        if (! class_exists($context)) {
            throw new LogicException("Unloadable: {$context}");
        }

        assert(class_exists($context));
        self::$context = $context;
        self::createModule();
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
        if ($isInjectable) {
            try {
                $object = self::getGrapher()->newInstanceArgs($type, $args);
            } catch (Unbound $unbound) {
                new Bind(self::getModuleInstance()->getContainer(), $type);
                $object = self::getGrapher()->newInstanceArgs($type, $args);
            }
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

    public static function getGrapher(): Grapher
    {
        $tmpDir = dirname((new ReflectionClass(AppModule::class))->getFileName()) . '/tmp';

        return new Grapher(self::getModuleInstance(), $tmpDir);
    }

    private static function createModule(): void
    {
        $context = new self::$context();
        assert(is_callable($context));
        self::$module = ($context)();
    }

    /**
     * Get singleton instance of Module class
     */
    private static function getModuleInstance(): AbstractModule
    {
        if (! self::$module instanceof AbstractModule) {
            self::createModule();
        }

        return self::$module;
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
