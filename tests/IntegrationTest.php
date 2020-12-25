<?php

declare(strict_types=1);

namespace Koriym\Dii;

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    /**
     * @var string
     */
    private static $host = '127.0.0.1:8080';

    /**
     * @var string
     */
    private $httpHost = 'http://127.0.0.1:8080';

    /**
     * @var BuiltinServer
     */
    private static $server;

    public static function setUpBeforeClass(): void
    {
        $publicDir = dirname(__DIR__) . '/demo/public';
        self::$server = new BuiltinServer(self::$host, $publicDir . '/index.php');
        self::$server->start();
        register_shutdown_function(function () {
            self::$server->stop();
        });
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    public function testIndex(): void
    {
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true
            ]
        ]);
        $responseBody  = file_get_contents('http://127.0.0.1:8080/', false, $context);
        $this->assertSame('Hello World +injected fake +intercepted', $responseBody);
    }
}