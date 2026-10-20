<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Integrations\PublicApiRequest;
use Illuminate\Support\Facades\Http;
use OverflowException;
use Tests\TestCase;

final class PublicApiRequestTest extends TestCase
{
    public function test_transport_connects_to_the_pinned_ip_but_preserves_the_original_host(): void
    {
        $keys = ['HTTP_PROXY', 'HTTPS_PROXY', 'ALL_PROXY', 'http_proxy', 'https_proxy', 'all_proxy'];
        $previous = [];
        foreach ($keys as $key) {
            $previous[$key] = [getenv($key), $_SERVER[$key] ?? null, $_ENV[$key] ?? null];
            putenv($key.'=http://127.0.0.1:1');
            $_SERVER[$key] = 'http://127.0.0.1:1';
            $_ENV[$key] = 'http://127.0.0.1:1';
        }

        try {
            $this->withServer('host', function (int $port): void {
                $response = (new PublicApiRequest)->get(
                    Http::acceptJson()->timeout(5),
                    "http://unresolvable.invalid:$port/data?token=abc%2B123",
                    '127.0.0.1',
                    2 * 1024 * 1024,
                );

                $this->assertSame(200, $response->status());
                $this->assertSame("unresolvable.invalid:$port", $response->json('host'));
                $this->assertSame('/data?token=abc%2B123', $response->json('request_target'));
            });
        } finally {
            foreach ($previous as $key => [$environment, $server, $env]) {
                $environment === false ? putenv($key) : putenv($key.'='.$environment);
                if ($server === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $server;
                }
                if ($env === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $env;
                }
            }
        }
    }

    public function test_transport_does_not_follow_redirects(): void
    {
        $this->withServer('redirect', function (int $port): void {
            $response = (new PublicApiRequest)->get(
                Http::acceptJson()->timeout(5),
                "http://unresolvable.invalid:$port/data",
                '127.0.0.1',
                2 * 1024 * 1024,
            );

            $this->assertSame(302, $response->status());
        });
    }

    public function test_transport_limits_the_decoded_gzip_body_before_buffering_it(): void
    {
        $this->withServer('gzip', function (int $port): void {
            $this->expectException(OverflowException::class);

            (new PublicApiRequest)->get(
                Http::acceptJson()->timeout(5),
                "http://unresolvable.invalid:$port/data",
                '127.0.0.1',
                2 * 1024 * 1024,
            );
        });
    }

    public function test_transport_accepts_a_small_decoded_gzip_body(): void
    {
        $this->withServer('gzip-small', function (int $port): void {
            $response = (new PublicApiRequest)->get(
                Http::acceptJson()->timeout(5),
                "http://unresolvable.invalid:$port/data",
                '127.0.0.1',
                2 * 1024 * 1024,
            );

            $this->assertSame(200, $response->status());
            $this->assertSame('ok', $response->json('result'));
        });
    }

    private function withServer(string $mode, callable $callback): void
    {
        $script = <<<'PHP'
$server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
if ($server === false) { exit(1); }
echo stream_socket_get_name($server, false), "\n";
fflush(STDOUT);
$connection = @stream_socket_accept($server, 10);
if ($connection === false) { exit(2); }
$request = '';
while (!str_contains($request, "\r\n\r\n")) {
    $chunk = fread($connection, 8192);
    if ($chunk === false || $chunk === '') { exit(3); }
    $request .= $chunk;
}
preg_match('/^Host: (.+)$/mi', $request, $matches);
$host = trim($matches[1] ?? '');
preg_match('/^GET (\S+) HTTP\/1\./', $request, $targetMatches);
$target = $targetMatches[1] ?? '';
if ($argv[1] === 'redirect') {
    $body = '';
    $headers = "HTTP/1.1 302 Found\r\nLocation: http://127.0.0.1/secret\r\n";
} elseif (str_starts_with($argv[1], 'gzip')) {
    $body = gzencode($argv[1] === 'gzip' ? str_repeat('x', 2 * 1024 * 1024 + 1) : '{"result":"ok"}');
    $headers = "HTTP/1.1 200 OK\r\nContent-Encoding: gzip\r\n";
} else {
    $body = json_encode(['host' => $host, 'request_target' => $target], JSON_THROW_ON_ERROR);
    $headers = "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n";
}
fwrite($connection, $headers.'Content-Length: '.strlen($body)."\r\nConnection: close\r\n\r\n".$body);
fclose($connection);
fclose($server);
PHP;
        $process = proc_open([PHP_BINARY, '-r', $script, $mode], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);
        $this->assertIsResource($process);

        try {
            $address = trim((string) fgets($pipes[1]));
            $this->assertMatchesRegularExpression('/^127\.0\.0\.1:\d+$/', $address);
            $callback((int) substr(strrchr($address, ':'), 1));
        } finally {
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_terminate($process);
            proc_close($process);
        }
    }
}
