<?php

declare(strict_types=1);

namespace Koriym\Dii;

use Koriym\Dii\Exception\CacheNotWritable;

use function dirname;
use function file_put_contents;
use function hash;
use function is_dir;
use function is_file;
use function mkdir;
use function rename;
use function substr;
use function tempnam;
use function unlink;
use function var_export;

use const LOCK_EX;

/**
 * Memoize a value as a `<?php return ...;` PHP file
 *
 * OPcache keeps the compiled file in memory, so this performs like a memory
 * cache without an extension such as APCu. Writes are atomic (tempnam + rename).
 */
final class FileCache implements CacheInterface
{
    public function __construct(private string $tmpDir)
    {
    }

    public function get(string $key, callable $callback): mixed
    {
        $file = $this->getFilename($key);
        if (is_file($file)) {
            return include $file;
        }

        $value = $callback();
        $this->write($file, '<?php return ' . var_export($value, true) . ';');

        return $value;
    }

    private function getFilename(string $key): string
    {
        $hash = hash('crc32b', $key);
        $dir = $this->tmpDir . '/' . substr($hash, 0, 2);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new CacheNotWritable("Cannot create cache directory: {$dir}");
        }

        return $dir . '/' . $hash . '.php';
    }

    /**
     * Write atomically (tempnam in the same directory, then rename)
     */
    private function write(string $file, string $code): void
    {
        $tmp = @tempnam(dirname($file), 'dii');
        if ($tmp === false) {
            throw new CacheNotWritable('Cannot create a temp file in: ' . dirname($file));
        }

        if (@file_put_contents($tmp, $code, LOCK_EX) === false || ! @rename($tmp, $file)) {
            @unlink($tmp);

            throw new CacheNotWritable("Cannot write cache file: {$file}");
        }
    }
}
