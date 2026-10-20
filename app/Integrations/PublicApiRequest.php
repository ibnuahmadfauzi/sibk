<?php

declare(strict_types=1);

namespace App\Integrations;

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use OverflowException;
use RuntimeException;
use Throwable;

final class PublicApiRequest
{
    /** @param array<string, int|string> $query */
    public function get(PendingRequest $request, string $url, string $address, int $maximumBytes, ?array $query = null): Response
    {
        if (! extension_loaded('curl') || ! defined('CURLOPT_CONNECT_TO')) {
            throw new RuntimeException('The cURL transport with CURLOPT_CONNECT_TO is required.');
        }

        $parts = parse_url($url);
        if (! is_array($parts) || ! isset($parts['host'], $parts['scheme'])) {
            throw new RuntimeException('The API URL is invalid.');
        }

        $host = $parts['host'];
        $port = (int) ($parts['port'] ?? (strtolower($parts['scheme']) === 'https' ? 443 : 80));
        $target = str_contains($address, ':') ? '['.$address.']' : $address;
        $overLimit = false;
        $curl = new CurlHandler;

        $request->withOptions([
            'allow_redirects' => false,
            'proxy' => '',
            'curl' => [CURLOPT_CONNECT_TO => [sprintf('%s:%d:%s:%d', $host, $port, $target, $port)]],
        ])->setHandler(static function ($psrRequest, array $options) use ($curl, $maximumBytes, &$overLimit) {
            $bytes = 0;
            $stream = Utils::streamFor(fopen('php://temp', 'w+'));
            $options['sink'] = FnStream::decorate($stream, [
                'write' => static function (string $chunk) use ($stream, $maximumBytes, &$bytes, &$overLimit): int {
                    $bytes += strlen($chunk);
                    if ($bytes > $maximumBytes) {
                        $overLimit = true;

                        throw new OverflowException('API response exceeds the configured size limit.');
                    }

                    return $stream->write($chunk);
                },
            ]);

            return $curl($psrRequest, $options);
        });

        try {
            $response = $query === null ? $request->get($url) : $request->get($url, $query);
        } catch (Throwable $exception) {
            if ($overLimit) {
                throw new OverflowException('API response exceeds the configured size limit.', 0, $exception);
            }

            throw $exception;
        }

        if ($overLimit) {
            throw new OverflowException('API response exceeds the configured size limit.');
        }

        return $response;
    }
}
