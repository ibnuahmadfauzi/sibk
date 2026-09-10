<?php

declare(strict_types=1);

namespace App\Integrations;

use Carbon\CarbonImmutable;

final readonly class IntegrationSettingState
{
    public const string STATE_UNCONFIGURED = 'unconfigured';

    public const string STATE_DRAFT = 'draft';

    public const string STATE_BLOCKED = 'blocked';

    public const string STATE_TEST_FAILED = 'test_failed';

    public const string STATE_READY = 'ready';

    public const string STATE_ACTIVE = 'active';

    public function __construct(
        public string $provider,
        public string $label,
        public ?string $baseUrl,
        public ?string $expectedSourceIdentifier,
        public int $timeoutSeconds,
        public bool $hasCredentials,
        public string $state,
        public int $configurationVersion,
        public int $operationFenceVersion,
        public ?int $verifiedConfigurationVersion,
        public ?string $verifiedDriverId,
        public ?string $verifiedAdapterVersion,
        public ?string $verifiedContractVersion,
        public ?string $verifiedEndpointPolicyDigest,
        public string $lastTestStatus,
        public ?string $lastTestCode,
        public ?CarbonImmutable $lastTestedAt,
        public bool $isEnabled,
        public bool $adapterAvailable,
        public bool $canTest,
        public bool $canActivate,
        public bool $canDeactivate,
    ) {}
}
