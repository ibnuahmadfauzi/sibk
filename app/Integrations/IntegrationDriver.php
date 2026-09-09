<?php

declare(strict_types=1);

namespace App\Integrations;

interface IntegrationDriver
{
    /** @var list<string> */
    public const array RESULT_CODES = [
        'success',
        'adapter_unavailable',
        'incomplete_configuration',
        'endpoint_not_allowed',
        'credential_unreadable',
        'busy',
        'timeout',
        'connection_failed',
        'authentication_rejected',
        'rate_limited',
        'remote_unavailable',
        'contract_invalid',
        'source_identity_mismatch',
        'response_too_large',
        'configuration_changed',
    ];

    public function id(): string;

    public function adapterVersion(): string;

    public function contractVersion(): string;

    public function isAvailable(): bool;

    public function probe(IntegrationRuntimeConfiguration $configuration): IntegrationProbeResult;
}
