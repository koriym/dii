<?php

declare(strict_types=1);

namespace Koriym\Dii;

use Koriym\Dii\Exception\DirectoryNotFoundException;
use Koriym\Dii\InjectableModuleFake\AlreadyBoundInjectable;
use Koriym\Dii\InjectableModuleFake\PlainClass;
use Koriym\Dii\InjectableModuleFake\Replacement\AlreadyBoundReplacement;
use Koriym\Dii\InjectableModuleFake\ScannedInjectable;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Name;

use function dirname;

final class InjectableModuleTest extends TestCase
{
    public const FAKE_DIR = __DIR__ . '/../Fake/InjectableModuleFake';

    public function testInjectableClassesGetBound(): void
    {
        $module = new InjectableModule([self::FAKE_DIR]);
        $container = $module->getContainer();

        $this->assertArrayHasKey($this->bindingKey(ScannedInjectable::class), $container->getContainer());
        $this->assertInstanceOf(ScannedInjectable::class, $container->getInstance(ScannedInjectable::class));
    }

    public function testClassesWithoutInjectableAreSkipped(): void
    {
        $module = new InjectableModule([self::FAKE_DIR]);

        $this->assertArrayNotHasKey($this->bindingKey(PlainClass::class), $module->getContainer()->getContainer());
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

    public function testDirectoryNotFound(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

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
