<?php

declare(strict_types=1);

namespace App\Integrations;

use InvalidArgumentException;
use JsonException;

final class IntegrationEndpointPolicy
{
    public const string VERSION = '1';

    /** @var list<string> */
    private array $allowedOrigins;

    /**
     * @param  list<string>  $allowedOrigins
     */
    public function __construct(
        array $allowedOrigins,
        private readonly bool $allowPrivateNetworks,
    ) {
        if ($allowedOrigins === []) {
            throw new InvalidArgumentException('Integration endpoint allowlist must not be empty.');
        }

        $canonicalOrigins = [];

        foreach ($allowedOrigins as $origin) {
            if (! is_string($origin) || $origin === '') {
                throw new InvalidArgumentException('Integration endpoint allowlist contains an invalid origin.');
            }

            $canonicalOrigins[] = $this->canonicalOrigin($origin, true);
        }

        $canonicalOrigins = array_values(array_unique($canonicalOrigins));
        sort($canonicalOrigins, SORT_STRING);

        $this->allowedOrigins = $canonicalOrigins;
    }

    public function assertAllowedEndpoint(string $endpoint): string
    {
        $parts = $this->parseUrl($endpoint);
        $origin = $this->originFromParts($parts);

        if (! in_array($origin, $this->allowedOrigins, true)) {
            throw new InvalidArgumentException('Integration endpoint origin is not allowed.');
        }

        if ($parts['scheme'] === 'http' && ! $this->allowPrivateNetworks) {
            throw new InvalidArgumentException('Plain HTTP is allowed only for opted-in private networks.');
        }

        return $origin.($parts['path'] ?? '');
    }

    /**
     * Validate caller-supplied resolution and the addresses bound for the connection.
     * No DNS lookup is performed here so the transport can bind only to validated IPs.
     *
     * @param  list<string>  $resolvedAddresses
     * @param  list<string>  $connectionAddresses
     * @return list<string>
     */
    public function assertStableResolution(
        string $endpoint,
        array $resolvedAddresses,
        array $connectionAddresses,
    ): array {
        $canonicalEndpoint = $this->assertAllowedEndpoint($endpoint);
        $resolved = $this->validateAddresses($resolvedAddresses);
        $connection = $this->validateAddresses($connectionAddresses);

        if ($resolved !== $connection) {
            throw new InvalidArgumentException('Integration endpoint resolution changed before connection.');
        }

        $hasInternal = false;
        $hasPublic = false;

        foreach ($connection as $address) {
            if ($this->isInternal($address)) {
                $hasInternal = true;
            } else {
                $hasPublic = true;
            }
        }

        if ($hasInternal && $hasPublic) {
            throw new InvalidArgumentException('Mixed public and internal DNS answers are not allowed.');
        }

        if ($hasInternal && ! $this->allowPrivateNetworks) {
            throw new InvalidArgumentException('Private and loopback addresses require provider opt-in.');
        }

        if (str_starts_with($canonicalEndpoint, 'http://') && $hasPublic) {
            throw new InvalidArgumentException('Plain HTTP endpoints must resolve only to private addresses.');
        }

        return $connection;
    }

    public function digest(): string
    {
        try {
            $canonicalPolicy = json_encode([
                'policy_version' => self::VERSION,
                'allowed_origins' => $this->allowedOrigins,
                'allow_private_networks' => $this->allowPrivateNetworks,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Integration endpoint policy cannot be encoded.', 0, $exception);
        }

        return hash('sha256', $canonicalPolicy);
    }

    private function canonicalOrigin(string $url, bool $originOnly): string
    {
        $parts = $this->parseUrl($url);

        if ($originOnly && isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/') {
            throw new InvalidArgumentException('Integration allowlist entries must be origins without a path.');
        }

        if ($parts['scheme'] === 'http' && ! $this->allowPrivateNetworks) {
            throw new InvalidArgumentException('Plain HTTP origins require private-network opt-in.');
        }

        return $this->originFromParts($parts);
    }

    /**
     * @return array{scheme: string, host: string, port?: int, path?: string}
     */
    private function parseUrl(string $url): array
    {
        if ($url === '' || trim($url) !== $url || preg_match('/[\\x00-\\x20\\x7f\\\\]/', $url) === 1) {
            throw new InvalidArgumentException('Integration endpoint URL is malformed.');
        }

        if (str_contains($url, '?') || str_contains($url, '#')) {
            throw new InvalidArgumentException('Integration endpoint query and fragment are not allowed.');
        }

        try {
            $parts = parse_url($url);
        } catch (\ValueError $exception) {
            throw new InvalidArgumentException('Integration endpoint URL is malformed.', 0, $exception);
        }

        if (! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            throw new InvalidArgumentException('Integration endpoint must be an absolute URL without user-info.');
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Integration endpoint scheme is not allowed.');
        }

        $host = $this->canonicalHost($parts['host']);
        $port = $parts['port'] ?? null;
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException('Integration endpoint port is invalid.');
        }

        $normalized = [
            'scheme' => $scheme,
            'host' => $host,
        ];

        if ($port !== null) {
            $normalized['port'] = $port;
        }

        if (isset($parts['path'])) {
            if (! str_starts_with($parts['path'], '/')) {
                throw new InvalidArgumentException('Integration endpoint path is invalid.');
            }

            $normalized['path'] = $parts['path'];
        }

        return $normalized;
    }

    private function canonicalHost(string $host): string
    {
        $host = strtolower($host);

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $address = substr($host, 1, -1);
            $packed = @inet_pton($address);

            if ($packed === false || strlen($packed) !== 16) {
                throw new InvalidArgumentException('Integration endpoint host is invalid.');
            }

            return '['.inet_ntop($packed).']';
        }

        $host = rtrim($host, '.');
        if ($host === ''
            || strlen($host) > 253
            || preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/', $host) !== 1
        ) {
            throw new InvalidArgumentException('Integration endpoint host is invalid.');
        }

        return $host;
    }

    /**
     * @param  array{scheme: string, host: string, port?: int}  $parts
     */
    private function originFromParts(array $parts): string
    {
        $defaultPort = $parts['scheme'] === 'https' ? 443 : 80;
        $port = $parts['port'] ?? $defaultPort;

        return $parts['scheme'].'://'.$parts['host'].($port === $defaultPort ? '' : ':'.$port);
    }

    /**
     * @param  list<string>  $addresses
     * @return list<string>
     */
    private function validateAddresses(array $addresses): array
    {
        if ($addresses === []) {
            throw new InvalidArgumentException('Integration endpoint resolution must not be empty.');
        }

        $canonical = [];

        foreach ($addresses as $address) {
            if (! is_string($address) || $address === '') {
                throw new InvalidArgumentException('Integration endpoint resolution contains an invalid address.');
            }

            $packed = @inet_pton($address);
            if ($packed === false) {
                throw new InvalidArgumentException('Integration endpoint resolution contains an invalid address.');
            }

            if (strlen($packed) === 16
                && substr($packed, 0, 10) === str_repeat("\0", 10)
                && substr($packed, 10, 2) === "\xff\xff"
            ) {
                $packed = substr($packed, 12, 4);
            }

            $normalized = inet_ntop($packed);
            if ($this->isAlwaysForbidden($normalized)) {
                throw new InvalidArgumentException('Integration endpoint resolved to a forbidden address.');
            }

            $canonical[] = $normalized;
        }

        $canonical = array_values(array_unique($canonical));
        sort($canonical, SORT_STRING);

        return $canonical;
    }

    private function isAlwaysForbidden(string $address): bool
    {
        return $this->matchesCidr($address, '0.0.0.0', 8)
            || $this->matchesCidr($address, '169.254.0.0', 16)
            || $this->matchesCidr($address, '224.0.0.0', 4)
            || $this->matchesCidr($address, '240.0.0.0', 4)
            || $address === '100.100.100.200'
            || $address === '::'
            || $this->matchesCidr($address, 'fe80::', 10)
            || $this->matchesCidr($address, 'ff00::', 8)
            || $address === 'fd00:ec2::254';
    }

    private function isInternal(string $address): bool
    {
        return $this->matchesCidr($address, '10.0.0.0', 8)
            || $this->matchesCidr($address, '172.16.0.0', 12)
            || $this->matchesCidr($address, '192.168.0.0', 16)
            || $this->matchesCidr($address, '127.0.0.0', 8)
            || $this->matchesCidr($address, 'fc00::', 7)
            || $address === '::1';
    }

    private function matchesCidr(string $address, string $network, int $prefixLength): bool
    {
        $packedAddress = @inet_pton($address);
        $packedNetwork = @inet_pton($network);

        if ($packedAddress === false
            || $packedNetwork === false
            || strlen($packedAddress) !== strlen($packedNetwork)
        ) {
            return false;
        }

        $wholeBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($wholeBytes > 0
            && substr($packedAddress, 0, $wholeBytes) !== substr($packedNetwork, 0, $wholeBytes)
        ) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($packedAddress[$wholeBytes]) & $mask)
            === (ord($packedNetwork[$wholeBytes]) & $mask);
    }
}
