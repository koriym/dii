<?php

declare(strict_types=1);

namespace Koriym\Dii;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function array_key_first;
use function file_put_contents;
use function is_dir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class FileCacheTest extends TestCase
{
    private string $dir;

    public function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/dii-filecache-test-' . uniqid();

        parent::setUp();
    }

    public function tearDown(): void
    {
        if (is_dir($this->dir)) {
            $this->removeRecursively($this->dir);
        }

        parent::tearDown();
    }

    public function testValueIsMemoizedAcrossCalls(): void
    {
        $cache = new FileCache($this->dir);
        $calls = 0;
        $build = static function () use (&$calls) {
            $calls++;

            return 'built';
        };

        $this->assertSame('built', $cache->get('key', $build));
        $this->assertSame('built', $cache->get('key', $build));
        $this->assertSame(1, $calls);
    }

    public function testCorruptedCacheFileIsRebuiltRatherThanFatal(): void
    {
        $cache = new FileCache($this->dir);
        $cache->get('key', static fn () => 'original');

        file_put_contents($this->onlyCacheFile(), '<?php return ');

        $calls = 0;
        $rebuild = static function () use (&$calls) {
            $calls++;

            return 'rebuilt';
        };

        $this->assertSame('rebuilt', $cache->get('key', $rebuild));
        $this->assertSame(1, $calls);

        // The rebuilt file must itself be a valid, repaired cache entry.
        $this->assertSame('rebuilt', $cache->get('key', static function () use (&$calls) {
            $calls++;

            return 'should-not-run';
        }));
        $this->assertSame(1, $calls);
    }

    private function onlyCacheFile(): string
    {
        $files = [];
        $directory = new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS);
        foreach (new RecursiveIteratorIterator($directory) as $file) {
            $files[] = (string) $file;
        }

        return $files[array_key_first($files)];
    }

    private function removeRecursively(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $path) {
            $path->isDir() ? rmdir((string) $path) : unlink((string) $path);
        }

        rmdir($dir);
    }
}
