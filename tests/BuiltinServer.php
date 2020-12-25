<?php

declare(strict_types=1);

namespace Koriym\Dii;

use function error_log;
use function register_shutdown_function;
use RuntimeException;
use function sprintf;
use function strpos;
use Symfony\Component\Process\Process;

final class BuiltinServer
{
    /**
     * @var Process
     */
    private $process;

    /**
     * @var string
     */
    private $host;

    public function __construct(string $host, string $index)
    {
        $this->process = new Process([
            PHP_BINARY,
            '-S',
            $host,
            $index
        ]);
        $this->host = $host;
        register_shutdown_function(function () {
            $this->process->stop();
        });
    }

    public function start() : void
    {
        $this->process->start();
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            sleep(1);

            return;
        }
        $this->process->waitUntil(function (string $type, string $output) : bool {
            if ($type === 'err' && ! is_int(strpos($output, 'started'))) {
                error_log($output);
            }

            return (bool) strpos($output, $this->host);
        });
    }

    public function stop() : void
    {
        $exitCode = $this->process->stop();
        if ($exitCode !== 143) {
            throw new RuntimeException(sprintf('code:%s msg:%s', (string) $exitCode, $this->process->getErrorOutput()));
        }
    }
}
