<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Transport\Vodz;

/** `php -S` running tests/Support/vodz-server.php, a stand-in for the ws2 endpoint. */
final class LocalVodzServer
{
    /** @param resource $process */
    private function __construct(private $process, private readonly int $port)
    {
    }

    public static function start(): self
    {
        $probe = stream_socket_server('tcp://127.0.0.1:0');
        if ($probe === false) {
            throw new \RuntimeException('cannot allocate a port');
        }
        $port = (int)substr((string)strrchr((string)stream_socket_get_name($probe, false), ':'), 1);
        fclose($probe);
        $router = dirname(__DIR__, 3) . '/Support/vodz-server.php';
        $process = proc_open(
            [PHP_BINARY, '-n', '-S', '127.0.0.1:' . $port, $router],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );
        if (!is_resource($process)) {
            throw new \RuntimeException('cannot start php -S');
        }
        for ($i = 0; $i < 100; $i++) {
            if ($c = @fsockopen('127.0.0.1', $port)) {
                fclose($c);
                return new self($process, $port);
            }
            usleep(50_000);
        }
        throw new \RuntimeException('php -S did not come up');
    }

    public function url(string $path): string
    {
        return 'http://127.0.0.1:' . $this->port . $path;
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }
    }
}
