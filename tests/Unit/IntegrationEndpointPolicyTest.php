<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Integrations\IntegrationEndpointPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IntegrationEndpointPolicyTest extends TestCase
{
    public function test_it_accepts_only_the_exact_canonical_origin_and_effective_port(): void
    {
        $policy = new IntegrationEndpointPolicy(
            ['HTTPS://API.EXAMPLE.SCH.ID:443', 'https://api.example.sch.id:8443'],
            false,
        );

        self::assertSame(
            'https://api.example.sch.id/v1/',
            $policy->assertAllowedEndpoint('https://API.EXAMPLE.SCH.ID:443/v1/'),
        );
        self::assertSame(
            'https://api.example.sch.id:8443/v1',
            $policy->assertAllowedEndpoint('https://api.example.sch.id:8443/v1'),
        );
    }

    #[DataProvider('disallowedEndpointProvider')]
    public function test_it_rejects_unsafe_or_non_allowlisted_endpoints(string $endpoint): void
    {
        $policy = new IntegrationEndpointPolicy(['https://api.example.sch.id'], false);

        $this->expectException(InvalidArgumentException::class);

        $policy->assertAllowedEndpoint($endpoint);
    }

    /** @return iterable<string, array{string}> */
    public static function disallowedEndpointProvider(): iterable
    {
        yield 'relative URL' => ['/v1'];
        yield 'user name' => ['https://user@api.example.sch.id'];
        yield 'password' => ['https://user:secret@api.example.sch.id'];
        yield 'query' => ['https://api.example.sch.id?next=outside'];
        yield 'empty query' => ['https://api.example.sch.id?'];
        yield 'fragment' => ['https://api.example.sch.id#section'];
        yield 'empty fragment' => ['https://api.example.sch.id#'];
        yield 'suffix host' => ['https://api.example.sch.id.attacker.test'];
        yield 'different port' => ['https://api.example.sch.id:8443'];
        yield 'outside allowlist' => ['https://other.example.sch.id'];
        yield 'public plain HTTP' => ['http://api.example.sch.id'];
    }

    #[DataProvider('invalidAllowlistProvider')]
    public function test_it_rejects_empty_or_non_origin_allowlist_entries(array $allowedOrigins): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IntegrationEndpointPolicy($allowedOrigins, false);
    }

    /** @return iterable<string, array{array<int, string>}> */
    public static function invalidAllowlistProvider(): iterable
    {
        yield 'empty list' => [[]];
        yield 'empty entry' => [['']];
        yield 'wildcard host' => [['https://*.example.sch.id']];
        yield 'suffix wildcard' => [['https://example.sch.id*']];
        yield 'origin path' => [['https://api.example.sch.id/v1']];
        yield 'origin query' => [['https://api.example.sch.id?x=1']];
        yield 'HTTP without private opt-in' => [['http://dapodik.internal']];
    }

    public function test_internal_http_requires_exact_allowlist_private_opt_in_and_only_internal_addresses(): void
    {
        $policy = new IntegrationEndpointPolicy(['http://dapodik.internal:8080'], true);

        self::assertSame(
            'http://dapodik.internal:8080/api',
            $policy->assertAllowedEndpoint('http://DAPODIK.INTERNAL:8080/api'),
        );
        self::assertSame(
            ['10.20.30.40'],
            $policy->assertStableResolution(
                'http://dapodik.internal:8080/api',
                ['10.20.30.40'],
                ['10.20.30.40'],
            ),
        );

        $this->expectException(InvalidArgumentException::class);

        $policy->assertStableResolution(
            'http://dapodik.internal:8080/api',
            ['203.0.113.10'],
            ['203.0.113.10'],
        );
    }

    #[DataProvider('alwaysForbiddenAddressProvider')]
    public function test_it_always_rejects_dangerous_address_classes(string $address): void
    {
        $policy = new IntegrationEndpointPolicy(['https://api.example.sch.id'], true);

        $this->expectException(InvalidArgumentException::class);

        $policy->assertStableResolution(
            'https://api.example.sch.id',
            [$address],
            [$address],
        );
    }

    /** @return iterable<string, array{string}> */
    public static function alwaysForbiddenAddressProvider(): iterable
    {
        yield 'IPv4 metadata' => ['169.254.169.254'];
        yield 'IPv6 metadata' => ['fd00:ec2::254'];
        yield 'IPv4 link local' => ['169.254.20.10'];
        yield 'IPv6 link local' => ['fe80::1'];
        yield 'IPv4 multicast' => ['224.0.0.1'];
        yield 'IPv6 multicast' => ['ff02::1'];
        yield 'IPv4 unspecified' => ['0.0.0.0'];
        yield 'IPv6 unspecified' => ['::'];
        yield 'IPv4 shared address space' => ['100.64.0.1'];
        yield 'IPv4 shared address space upper boundary' => ['100.127.255.255'];
        yield 'IPv4 benchmarking' => ['198.18.0.1'];
        yield 'IPv4 benchmarking upper boundary' => ['198.19.255.255'];
        yield 'IPv4 IETF protocol assignment' => ['192.0.0.8'];
        yield 'IPv4 IETF protocol assignment upper boundary' => ['192.0.0.255'];
        yield 'IPv4 TEST-NET-1' => ['192.0.2.1'];
        yield 'IPv4 deprecated 6to4 relay' => ['192.88.99.1'];
        yield 'IPv4 TEST-NET-2' => ['198.51.100.1'];
        yield 'IPv4 TEST-NET-3' => ['203.0.113.1'];
        yield 'IPv6 NAT64' => ['64:ff9b::1'];
        yield 'IPv6 local translation' => ['64:ff9b:1::1'];
        yield 'IPv6 discard-only' => ['100::1'];
        yield 'IPv6 dummy' => ['100:0:0:1::1'];
        yield 'IPv6 IETF protocol assignment' => ['2001::1'];
        yield 'IPv6 IETF protocol assignment upper boundary' => ['2001:1ff:ffff:ffff:ffff:ffff:ffff:ffff'];
        yield 'IPv6 benchmarking' => ['2001:2::1'];
        yield 'IPv6 documentation' => ['2001:db8::1'];
        yield 'IPv6 6to4 tunneling' => ['2002::1'];
        yield 'IPv6 documentation 3fff' => ['3fff::1'];
        yield 'IPv6 SRv6 SID' => ['5f00::1'];
    }

    #[DataProvider('globalBoundaryAddressProvider')]
    public function test_addresses_immediately_outside_special_ranges_remain_public(string $address): void
    {
        $policy = new IntegrationEndpointPolicy(['https://api.example.sch.id'], false);

        self::assertSame(
            [$address],
            $policy->assertStableResolution(
                'https://api.example.sch.id',
                [$address],
                [$address],
            ),
        );
    }

    /** @return iterable<string, array{string}> */
    public static function globalBoundaryAddressProvider(): iterable
    {
        yield 'before shared IPv4' => ['100.63.255.255'];
        yield 'after shared IPv4' => ['100.128.0.0'];
        yield 'before benchmarking IPv4' => ['198.17.255.255'];
        yield 'after benchmarking IPv4' => ['198.20.0.0'];
        yield 'after IETF IPv4 block' => ['192.0.1.0'];
        yield 'before IETF IPv6 block' => ['2000:ffff:ffff:ffff:ffff:ffff:ffff:ffff'];
        yield 'after IETF IPv6 block' => ['2001:200::'];
        yield 'before IPv6 documentation block' => ['2001:db7:ffff:ffff:ffff:ffff:ffff:ffff'];
        yield 'after IPv6 documentation block' => ['2001:db9::'];
        yield 'after 6to4 IPv6 block' => ['2003::'];
    }

    #[DataProvider('unsafeLiteralOriginProvider')]
    public function test_literal_special_or_ambiguous_ip_origins_are_rejected_immediately(
        string $origin,
        bool $allowPrivateNetworks,
    ): void {
        $this->expectException(InvalidArgumentException::class);

        new IntegrationEndpointPolicy([$origin], $allowPrivateNetworks);
    }

    /** @return iterable<string, array{string, bool}> */
    public static function unsafeLiteralOriginProvider(): iterable
    {
        yield 'private IPv4 without opt-in' => ['https://127.0.0.1', false];
        yield 'private IPv6 without opt-in' => ['https://[::1]', false];
        yield 'shared IPv4 despite opt-in' => ['https://100.64.0.1', true];
        yield 'NAT64 IPv6 despite opt-in' => ['https://[64:ff9b::1]', true];
        yield 'integer IPv4' => ['https://2130706433', true];
        yield 'octal IPv4' => ['https://0177.0.0.1', true];
        yield 'hex IPv4' => ['https://0x7f000001', true];
        yield 'mapped private IPv6' => ['https://[::ffff:10.0.0.1]', false];
        yield 'mapped special IPv6' => ['https://[::ffff:192.0.2.1]', true];
        yield 'IPv6 zone identifier' => ['https://[fe80::1%25eth0]', true];
    }

    public function test_literal_private_origins_are_allowed_only_with_opt_in(): void
    {
        $ipv4 = new IntegrationEndpointPolicy(['https://127.0.0.1'], true);
        $ipv6 = new IntegrationEndpointPolicy(['https://[::1]'], true);

        self::assertSame('https://127.0.0.1/api', $ipv4->assertAllowedEndpoint('https://127.0.0.1/api'));
        self::assertSame('https://[::1]/api', $ipv6->assertAllowedEndpoint('https://[::1]/api'));
    }

    public function test_mapped_public_ipv6_inherits_the_ipv4_class(): void
    {
        $policy = new IntegrationEndpointPolicy(['https://[::ffff:8.8.8.8]'], false);

        self::assertSame(
            'https://[::ffff:8.8.8.8]/api',
            $policy->assertAllowedEndpoint('https://[::ffff:8.8.8.8]/api'),
        );
    }

    #[DataProvider('internalAddressProvider')]
    public function test_private_and_loopback_addresses_require_provider_opt_in(string $address): void
    {
        $policy = new IntegrationEndpointPolicy(['https://api.example.sch.id'], false);

        $this->expectException(InvalidArgumentException::class);

        $policy->assertStableResolution(
            'https://api.example.sch.id',
            [$address],
            [$address],
        );
    }

    /** @return iterable<string, array{string}> */
    public static function internalAddressProvider(): iterable
    {
        yield 'private IPv4' => ['10.10.0.8'];
        yield 'loopback IPv4' => ['127.0.0.1'];
        yield 'private IPv6' => ['fd12:3456::1'];
        yield 'loopback IPv6' => ['::1'];
    }

    public function test_it_rejects_mixed_public_and_internal_dns_answers(): void
    {
        $policy = new IntegrationEndpointPolicy(['https://api.example.sch.id'], true);

        $this->expectException(InvalidArgumentException::class);

        $policy->assertStableResolution(
            'https://api.example.sch.id',
            ['8.8.8.8', '10.10.0.8'],
            ['8.8.8.8', '10.10.0.8'],
        );
    }

    public function test_it_rejects_dns_answers_that_change_before_connection(): void
    {
        $policy = new IntegrationEndpointPolicy(['https://api.example.sch.id'], false);

        $this->expectException(InvalidArgumentException::class);

        $policy->assertStableResolution(
            'https://api.example.sch.id',
            ['8.8.8.8', '1.1.1.1'],
            ['8.8.8.8', '9.9.9.9'],
        );
    }

    public function test_it_accepts_the_same_public_dns_set_in_a_different_order(): void
    {
        $policy = new IntegrationEndpointPolicy(['https://api.example.sch.id'], false);

        self::assertSame(
            ['1.1.1.1', '8.8.8.8'],
            $policy->assertStableResolution(
                'https://api.example.sch.id',
                ['8.8.8.8', '1.1.1.1', '8.8.8.8'],
                ['1.1.1.1', '8.8.8.8'],
            ),
        );
    }

    public function test_policy_digest_is_canonical_and_changes_with_effective_policy(): void
    {
        $first = new IntegrationEndpointPolicy([
            'https://B.example.sch.id:443',
            'https://a.example.sch.id',
            'https://a.example.sch.id:443',
        ], false);
        $equivalent = new IntegrationEndpointPolicy([
            'https://a.example.sch.id',
            'https://b.example.sch.id',
        ], false);
        $privateEnabled = new IntegrationEndpointPolicy([
            'https://a.example.sch.id',
            'https://b.example.sch.id',
        ], true);
        $differentOrigin = new IntegrationEndpointPolicy([
            'https://a.example.sch.id',
            'https://c.example.sch.id',
        ], false);

        self::assertSame(
            '0de93a30367a3a98d5bc53532c3c6b2f5645d9af112c6ac418f4537fb9f14219',
            $first->digest(),
        );
        self::assertSame($first->digest(), $equivalent->digest());
        self::assertNotSame($first->digest(), $privateEnabled->digest());
        self::assertNotSame($first->digest(), $differentOrigin->digest());
    }
}
