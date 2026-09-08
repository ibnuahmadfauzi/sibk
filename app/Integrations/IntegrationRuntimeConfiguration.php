<?php

declare(strict_types=1);

namespace App\Integrations;

final readonly class IntegrationRuntimeConfiguration
{
    /**
     * @param  array{type: string, token: string}  $credentials
     */
    public function __construct(
        public string $provider,
        public string $baseUrl,
        public string $expectedSourceIdentifier,
        #[\SensitiveParameter]
        private array $credentials,
        public int $timeoutSeconds,
        public int $configurationVersion,
        public int $operationFenceVersion,
        public string $endpointPolicyDigest,
    ) {}

    /**
     * @return array{type: string, token: string}
     */
    public function credentials(): array
    {
        return $this->credentials;
    }
}
