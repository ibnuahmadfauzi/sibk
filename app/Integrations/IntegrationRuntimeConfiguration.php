<?php

declare(strict_types=1);

namespace App\Integrations;

use LogicException;
use SensitiveParameterValue;

final readonly class IntegrationRuntimeConfiguration
{
    private SensitiveParameterValue $credentials;

    /**
     * @param  array{type: string, token: string}  $credentials
     */
    public function __construct(
        public string $provider,
        public string $baseUrl,
        public string $expectedSourceIdentifier,
        #[\SensitiveParameter]
        array $credentials,
        public int $timeoutSeconds,
        public int $configurationVersion,
        public int $operationFenceVersion,
        public string $endpointPolicyDigest,
    ) {
        $this->credentials = new SensitiveParameterValue($credentials);
    }

    /**
     * @return array{type: string, token: string}
     */
    public function credentials(): array
    {
        /** @var array{type: string, token: string} $credentials */
        $credentials = $this->credentials->getValue();

        return $credentials;
    }

    /**
     * @return never
     */
    public function __serialize(): array
    {
        throw new LogicException('Integration runtime configuration cannot be serialized.');
    }

    /**
     * @return array<string, bool|int|string>
     */
    public function __debugInfo(): array
    {
        return [
            'provider' => $this->provider,
            'baseUrl' => $this->baseUrl,
            'expectedSourceIdentifier' => $this->expectedSourceIdentifier,
            'credentials' => '[REDACTED]',
            'timeoutSeconds' => $this->timeoutSeconds,
            'configurationVersion' => $this->configurationVersion,
            'operationFenceVersion' => $this->operationFenceVersion,
            'endpointPolicyDigest' => $this->endpointPolicyDigest,
        ];
    }
}
