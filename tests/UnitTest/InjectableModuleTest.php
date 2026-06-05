<?php

declare(strict_types=1);

namespace Koriym\Dii;

use Koriym\Dii\Exception\DirectoryNotFound;
use Koriym\Dii\InjectableModuleFake\AbstractInjectable;
use Koriym\Dii\InjectableModuleFake\AlreadyBoundInjectable;
use Koriym\Dii\InjectableModuleFake\AopInterceptor;
use Koriym\Dii\InjectableModuleFake\AopScannedInjectable;
use Koriym\Dii\InjectableModuleFake\ChildInjectable;
use Koriym\Dii\InjectableModuleFake\ConcreteFromAbstractInjectable;
use Koriym\Dii\InjectableModuleFake\PlainClass;
use Koriym\Dii\InjectableModuleFake\Replacement\AlreadyBoundReplacement;
use Koriym\Dii\InjectableModuleFake\ScannedInjectable;
use Koriym\Dii\InjectableModuleFake\SideEffectClass;
use Koriym\Dii\InjectableModuleFake\Support\SideEffectFlag;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Name;

use function class_exists;
use function dirname;
use function sys_get_temp_dir;

final class InjectableModuleTest extends TestCase
{
    public const FAKE_DIR = __DIR__ . '/../Fake/InjectableModuleFake';

    public function testInjectableClassesGetBound(): void
    {
        $module = new InjectableModule([self::FAKE_DIR]);
        $container = $module->getContainer();

        $this->assertArrayHasKey($this->bindingKey(ScannedInjectable::class), $container->getContainer());
        $this->assertInstanceOf(ScannedInjectable::class, $container->getInstance(ScannedInjectable::class));
        $this->assertInstanceOf(ChildInjectable::class, $container->getInstance(ChildInjectable::class));
    }

    public function testClassesWithoutInjectableAreSkipped(): void
    {
        $module = new InjectableModule([self::FAKE_DIR]);

        $this->assertArrayNotHasKey($this->bindingKey(PlainClass::class), $module->getContainer()->getContainer());
    }

    public function testAbstractInjectableClassesAreSkippedButConcreteSubclassesAreBound(): void
    {
        $module = new InjectableModule([self::FAKE_DIR]);
        $container = $module->getContainer();

        $this->assertArrayNotHasKey($this->bindingKey(AbstractInjectable::class), $container->getContainer());
        $this->assertArrayHasKey($this->bindingKey(ConcreteFromAbstractInjectable::class), $container->getContainer());
        $this->assertInstanceOf(ConcreteFromAbstractInjectable::class, $container->getInstance(ConcreteFromAbstractInjectable::class));
    }

    public function testNonInjectableFilesAreNotLoaded(): void
    {
        SideEffectFlag::$loaded = false;

        new InjectableModule([self::FAKE_DIR]);

        $this->assertFalse(SideEffectFlag::$loaded);
        $this->assertFalse(class_exists(SideEffectClass::class, false));
    }

    public function testAlreadyBoundClassesAreNotOverriddenWhenInstalledLast(): void
    {
        $module = new class extends AbstractModule {
            protected function configure()
            {
                $this->bind(AlreadyBoundInjectable::class)->to(AlreadyBoundReplacement::class);
                $this->install(new InjectableModule([InjectableModuleTest::FAKE_DIR]));
            }
        };

        $instance = $module->getContainer()->getInstance(AlreadyBoundInjectable::class);

        $this->assertInstanceOf(AlreadyBoundReplacement::class, $instance);
    }

    public function testAlreadyBoundClassesAreNotOverriddenFromPreviousModule(): void
    {
        $explicitModule = new class extends AbstractModule {
            protected function configure()
            {
                $this->bind(AlreadyBoundInjectable::class)->to(AlreadyBoundReplacement::class);
            }
        };
        $module = new InjectableModule([self::FAKE_DIR], $explicitModule);

        $instance = $module->getContainer()->getInstance(AlreadyBoundInjectable::class);

        $this->assertInstanceOf(AlreadyBoundReplacement::class, $instance);
    }

    public function testScannedInjectableIsAopWoven(): void
    {
        $module = new class extends AbstractModule {
            protected function configure()
            {
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->startsWith('actionIndex'),
                    [AopInterceptor::class]
                );
                $this->install(new InjectableModule([InjectableModuleTest::FAKE_DIR]));
            }
        };

        $target = (new Injector($module, sys_get_temp_dir()))->getInstance(AopScannedInjectable::class);
        $target->actionIndex();

        $this->assertTrue($target->intercepted);
    }

    public function testDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFound::class);

        new InjectableModule([dirname(__DIR__) . '/Fake/InjectableModuleFakeMissing']);
    }

    /**
     * @param class-string $class
     */
    private function bindingKey(string $class): string
    {
        return $class . '-' . Name::ANY;
    }
}
