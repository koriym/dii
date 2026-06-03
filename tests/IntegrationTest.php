<?php

declare(strict_types=1);

namespace Koriym\Dii;

use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function file_get_contents;
use function passthru;
use function register_shutdown_function;
use function stream_context_create;

class IntegrationTest extends TestCase
{
    /** @var string */
    private static $host = '127.0.0.1:8081';

    /** @var BuiltinServer */
    private static $server;

    public static function setUpBeforeClass(): void
    {
        $demoDir = dirname(__DIR__) . '/demo';
        passthru('cd ' . escapeshellarg($demoDir) . ' && composer install --quiet', $exitCode);
        if ($exitCode !== 0) {
            self::fail("demo composer install failed with exit code {$exitCode}");
        }

        $publicDir = $demoDir . '/public';
        self::$server = new BuiltinServer(self::$host, $publicDir . '/index.php');
        self::$server->start();
        register_shutdown_function(static function () {
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
            'http' => ['ignore_errors' => true],
        ]);
        $responseBody  = file_get_contents('http://' . self::$host, false, $context);
        $this->assertSame('Hello World +injected(fake) +intercepted', $responseBody);
    }
}
