<?php

declare(strict_types=1);

namespace App\Integrations;

use InvalidArgumentException;
use JsonException;

final class IntegrationEndpointPolicy
{
    public const string VERSION = '1';

    private const string ADDRESS_PUBLIC = 'public';

    private const string ADDRESS_INTERNAL = 'internal';

    private const string ADDRESS_FORBIDDEN = 'forbidden';

    /** IANA IPv4 Special-Purpose Address Space; private/loopback are the only opt-in classes. @var list<array{string, int}> */
    private const array IPV4_INTERNAL_RANGES = [
        ['10.0.0.0', 8],
        ['127.0.0.0', 8],
        ['172.16.0.0', 12],
        ['192.168.0.0', 16],
    ];

    /** IANA IPv4 special-purpose ranges rejected fail-closed. @var list<array{string, int}> */
    private const array IPV4_SPECIAL_RANGES = [
        ['0.0.0.0', 8],
        ['100.64.0.0', 10],
        ['169.254.0.0', 16],
        ['192.0.0.0', 24],
        ['192.0.2.0', 24],
        ['192.31.196.0', 24],
        ['192.52.193.0', 24],
        ['192.88.99.0', 24],
        ['192.175.48.0', 24],
        ['198.18.0.0', 15],
        ['198.51.100.0', 24],
        ['203.0.113.0', 24],
        ['224.0.0.0', 4],
        ['240.0.0.0', 4],
    ];

    /** IANA IPv6 Special-Purpose Address Space; unique-local/loopback are the only opt-in classes. @var list<array{string, int}> */
    private const array IPV6_INTERNAL_RANGES = [
        ['::1', 128],
        ['fc00::', 7],
    ];

    /**
     * IANA special-purpose ranges are rejected conservatively, including the
     * small globally reachable exceptions nested inside broader special blocks.
     *
     * @var list<array{string, int}>
     */
    private const array IPV6_SPECIAL_RANGES = [
        ['::', 96],
        ['64:ff9b::', 96],
        ['64:ff9b:1::', 48],
        ['100::', 64],
        ['100:0:0:1::', 64],
        ['2001::', 23],
        ['2001:db8::', 32],
        ['2002::', 16],
        ['2620:4f:8000::', 48],
        ['3fff::', 20],
        ['5f00::', 16],
        ['fd00:ec2::254', 128],
        ['fe80::', 10],
        ['ff00::', 8],
    ];

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

        $hostData = $this->canonicalHost($parts['host']);
        $host = $hostData['host'];
        $port = $parts['port'] ?? null;
        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException('Integration endpoint port is invalid.');
        }

        $normalized = [
            'scheme' => $scheme,
            'host' => $host,
        ];

        if (isset($hostData['address_class'])) {
            if ($hostData['address_class'] === self::ADDRESS_FORBIDDEN) {
                throw new InvalidArgumentException('Integration endpoint literal address is not globally routable.');
            }

            if ($hostData['address_class'] === self::ADDRESS_INTERNAL && ! $this->allowPrivateNetworks) {
                throw new InvalidArgumentException('Private and loopback literal addresses require provider opt-in.');
            }

            if ($scheme === 'http' && $hostData['address_class'] === self::ADDRESS_PUBLIC) {
                throw new InvalidArgumentException('Plain HTTP endpoints must use opted-in private addresses.');
            }
        }

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

    /** @return array{host: string, address_class?: string} */
    private function canonicalHost(string $host): array
    {
        $host = strtolower($host);

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $address = substr($host, 1, -1);
            $packed = @inet_pton($address);

            if ($packed === false || strlen($packed) !== 16) {
                throw new InvalidArgumentException('Integration endpoint host is invalid.');
            }

            return [
                'host' => '['.inet_ntop($packed).']',
                'address_class' => $this->classifyPackedAddress($packed),
            ];
        }

        $packed = @inet_pton($host);
        if ($packed !== false && strlen($packed) === 4) {
            return [
                'host' => inet_ntop($packed),
                'address_class' => $this->classifyPackedAddress($packed),
            ];
        }

        if (preg_match('/^(?:0x[0-9a-f]+|[0-9]+)(?:\.(?:0x[0-9a-f]+|[0-9]+))*$/i', $host) === 1) {
            throw new InvalidArgumentException('Ambiguous numeric IPv4 hosts are not allowed.');
        }

        $host = rtrim($host, '.');
        if ($host === ''
            || strlen($host) > 253
            || preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/', $host) !== 1
        ) {
            throw new InvalidArgumentException('Integration endpoint host is invalid.');
        }

        return ['host' => $host];
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
            if ($this->classifyPackedAddress($packed) === self::ADDRESS_FORBIDDEN) {
                throw new InvalidArgumentException('Integration endpoint resolved to a forbidden address.');
            }

            $canonical[] = $normalized;
        }

        $canonical = array_values(array_unique($canonical));
        sort($canonical, SORT_STRING);

        return $canonical;
    }

    private function isInternal(string $address): bool
    {
        $packed = @inet_pton($address);

        return $packed !== false && $this->classifyPackedAddress($packed) === self::ADDRESS_INTERNAL;
    }

    private function classifyPackedAddress(string $packedAddress): string
    {
        if (strlen($packedAddress) === 16
            && substr($packedAddress, 0, 10) === str_repeat("\0", 10)
            && substr($packedAddress, 10, 2) === "\xff\xff"
        ) {
            return $this->classifyPackedAddress(substr($packedAddress, 12, 4));
        }

        $address = inet_ntop($packedAddress);
        $internalRanges = strlen($packedAddress) === 4
            ? self::IPV4_INTERNAL_RANGES
            : self::IPV6_INTERNAL_RANGES;
        $specialRanges = strlen($packedAddress) === 4
            ? self::IPV4_SPECIAL_RANGES
            : self::IPV6_SPECIAL_RANGES;

        if (strlen($packedAddress) === 16 && $this->matchesCidr($address, 'fd00:ec2::254', 128)) {
            return self::ADDRESS_FORBIDDEN;
        }

        foreach ($internalRanges as [$network, $prefixLength]) {
            if ($this->matchesCidr($address, $network, $prefixLength)) {
                return self::ADDRESS_INTERNAL;
            }
        }

        foreach ($specialRanges as [$network, $prefixLength]) {
            if ($this->matchesCidr($address, $network, $prefixLength)) {
                return self::ADDRESS_FORBIDDEN;
            }
        }

        if (strlen($packedAddress) === 16 && ! $this->matchesCidr($address, '2000::', 3)) {
            return self::ADDRESS_FORBIDDEN;
        }

        return self::ADDRESS_PUBLIC;
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
