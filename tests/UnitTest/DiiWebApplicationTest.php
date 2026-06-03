<?php

declare(strict_types=1);

namespace Koriym\Dii;

use application\controllers\NamespacedController;
use application\modules\some\SomeModule;
use CWebApplication;
use PHPUnit\Framework\TestCase;
use Yii;

use function dirname;
use function error_reporting;

use const E_DEPRECATED;

class DiiWebApplicationTest extends TestCase
{
    /** @var string[]|array[] */
    private $config;

    public function setUp(): void
    {
        // Yii's error handler (set_error_handler with error_reporting()) turns
        // PHP 8.5's "(double) cast is deprecated" notice into an HTML fatal.
        // Scope the suppression to this Yii-driven test.
        error_reporting(error_reporting() & ~E_DEPRECATED);
        $this->config = [
            'basePath' => dirname(__DIR__) . '/Fake/protected',
            'controllerMap' => [
                'namespaced' => [
                    'class' => NamespacedController::class,
                ],
            ],
            'modules' => [
                'some' => [
                    'class' => SomeModule::class,
                ],
            ],
        ];
        parent::setUp();
    }

    /**
     * @dataProvider routesProvider
     */
    public function testCreateController(string $route): void
    {
        [$diiController, $diiAction] = (new DiiWebApplication($this->config))->createController($route);
        Yii::setApplication(null);
        [$yiiController, $yiiAction] = (new CWebApplication($this->config))->createController($route);
        Yii::setApplication(null);

        $this->assertSame($yiiController->getId(), $diiController->getId());
        $this->assertSame($yiiAction, $diiAction);
    }

    /**
     * @return string[][]
     */
    public function routesProvider(): array
    {
        return [
            [''],
            ['site/foo'],
            ['subdir/subdirectory'],
            ['namespaced'],
            ['some/module'],
            ['some/subdir/subdirectory'],
        ];
    }
}
