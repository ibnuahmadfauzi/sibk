<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\IntegrationConfigurationProvider;
use App\Integrations\IntegrationDriverRegistry;
use App\Integrations\IntegrationOperationContext;
use App\Integrations\IntegrationOperationLock;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Integrations\IntegrationSettingState;
use App\Models\User;

final class IntegrationSettingService implements IntegrationConfigurationProvider
{
    private readonly IntegrationStateResolver $states;

    private readonly IntegrationSettingUpdater $updater;

    private readonly IntegrationConnectionTester $tester;

    private readonly IntegrationActivationService $activation;

    public function __construct(
        IntegrationDriverRegistry $drivers,
        IntegrationOperationLock $operationLock,
        AuditService $auditService,
    ) {
        $this->states = new IntegrationStateResolver($drivers, $operationLock);
        $this->updater = new IntegrationSettingUpdater($operationLock, $auditService, $this->states);
        $this->tester = new IntegrationConnectionTester($operationLock, $auditService, $this->states);
        $this->activation = new IntegrationActivationService($operationLock, $auditService, $this->states);
    }

    /** @return array{dapodik: IntegrationSettingState, etatib: IntegrationSettingState} */
    public function allStates(): array
    {
        return $this->states->allStates();
    }

    /** @param array<string, mixed> $data */
    public function save(
        string $provider,
        #[\SensitiveParameter] array $data,
        User $actor,
    ): IntegrationSettingState {
        return $this->updater->save($provider, $data, $actor);
    }

    public function testConnection(string $provider, User $actor): IntegrationSettingState
    {
        return $this->tester->test($provider, $actor);
    }

    public function activate(string $provider, User $actor): IntegrationSettingState
    {
        return $this->activation->activate($provider, $actor);
    }

    public function deactivate(string $provider, User $actor): IntegrationSettingState
    {
        return $this->activation->deactivate($provider, $actor);
    }

    public function active(
        string $provider,
        string $driverId,
        string $adapterVersion,
        string $contractVersion,
        ?IntegrationOperationContext $context = null,
    ): IntegrationRuntimeConfiguration {
        return $this->states->active(
            $provider,
            $driverId,
            $adapterVersion,
            $contractVersion,
            $context,
        );
    }

    public function assertCurrent(
        string $provider,
        int $expectedVersion,
        string $driverId,
        string $adapterVersion,
        string $contractVersion,
        ?IntegrationOperationContext $context = null,
    ): void {
        $this->states->assertCurrent(
            $provider,
            $expectedVersion,
            $driverId,
            $adapterVersion,
            $contractVersion,
            $context,
        );
    }
}
