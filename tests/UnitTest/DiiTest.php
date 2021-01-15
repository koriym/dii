<?php

declare(strict_types=1);

namespace Koriym\Dii;

use CException;
use CLogRouter;
use Koriym\Dii\Context\Test;
use PHPUnit\Framework\TestCase;

class DiiTest extends TestCase
{
    /** @var Dii */
    private $dii;

    public function setUp(): void
    {
        $this->dii = new Dii();
        Dii::setContext(App::class);
        parent::setUp();
    }

    public function testNewInstance(): void
    {
        $this->assertInstanceOf(Dii::class, $this->dii);
    }

    public function testCreateCreateComponentWithoutGrapher(): void
    {
        $config = [
            'class' => 'CLogRouter',
            'routes' => [
                ['class' => 'CFileLogRoute', 'levels' => 'error, warning'],
                ['class' => 'CWebLogRoute'],
            ],
        ];
        $router = $this->dii->createComponent($config);
        $this->assertInstanceOf(CLogRouter::class, $router);
    }

    public function testCreateComponentWithGrapher(): FakeSiteController
    {
        $controller = $this->dii->createComponent(FakeSiteController::class, 'site');
        $this->assertInstanceOf(FakeSiteController::class, $controller);

        return $controller;
    }

    /**
     * @depends testCreateComponentWithGrapher
     */
    public function testDependencyInjected(FakeSiteController $controller): void
    {
        $this->assertInstanceOf(Foo::class, $controller->foo);
    }

    /**
     * @depends testCreateComponentWithGrapher
     */
    public function testIntercepted(FakeSiteController $controller): void
    {
        $controller->actionIndex();
        $this->assertTrue($controller->intercepted);
    }

    public function testSetContext(): void
    {
        $this->dii->setContext(TestContext::class); // FooInterface -> TestFoo
        $controller = $this->dii->createComponent(FakeSiteController::class, 'site');
        $this->assertInstanceOf(FakeSiteController::class, $controller);
        $this->assertNotInstanceOf(Foo::class, $controller->foo);
        $this->assertInstanceOf(TestFoo::class, $controller->foo);
    }

    public function testInvalidArgument(): void
    {
        $this->expectException(CException::class);
        $this->dii->createComponent([]); // no class
    }
}
