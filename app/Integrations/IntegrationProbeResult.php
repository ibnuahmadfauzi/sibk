<?php

declare(strict_types=1);

namespace App\Integrations;

use InvalidArgumentException;

final readonly class IntegrationProbeResult
{
    public const string CODE_SUCCESS = 'success';

    public const string CODE_ADAPTER_UNAVAILABLE = 'adapter_unavailable';

    /** @var list<string> */
    public const array RESULT_CODES = [
        self::CODE_SUCCESS,
        self::CODE_ADAPTER_UNAVAILABLE,
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

    public function __construct(
        public string $code,
        public string $driverId,
        public string $adapterVersion,
        public string $contractVersion,
        public ?string $reportedSourceIdentifier,
        public bool $schemaValid,
        public bool $completenessVerified,
    ) {
        if (! in_array($code, self::RESULT_CODES, true)) {
            throw new InvalidArgumentException('Integration probe result code is not allowed.');
        }
    }
}
