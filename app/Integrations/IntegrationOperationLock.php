<?php

declare(strict_types=1);

namespace App\Integrations;

use App\Models\IntegrationSetting;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class IntegrationOperationLock
{
    public const int LEASE_TTL_SECONDS = 60;

    public const int HARD_DEADLINE_SECONDS = 50;

    public const int SAFETY_MARGIN_SECONDS = 5;

    public function __construct(
        private readonly CacheFactory $cache,
        private readonly int $leaseTtlSeconds = self::LEASE_TTL_SECONDS,
        private readonly int $hardDeadlineSeconds = self::HARD_DEADLINE_SECONDS,
        private readonly int $safetyMarginSeconds = self::SAFETY_MARGIN_SECONDS,
    ) {
        if ($leaseTtlSeconds < 1
            || $hardDeadlineSeconds < 1
            || $safetyMarginSeconds < 0
            || $hardDeadlineSeconds + $safetyMarginSeconds > $leaseTtlSeconds
        ) {
            throw new InvalidArgumentException('Integration operation deadline must fit safely within the lock lease.');
        }
    }

    /**
     * @template TResult
     *
     * @param  Closure(IntegrationOperationContext): TResult  $operation
     * @return TResult
     */
    public function run(string $provider, #[\SensitiveParameter] Closure $operation): mixed
    {
        $this->assertProvider($provider);
        $lock = $this->cache->store()->lock($this->lockName($provider), $this->leaseTtlSeconds);

        if (! $lock->get()) {
            throw new IntegrationBusyException;
        }

        try {
            $startedAt = CarbonImmutable::now();
            $fencingToken = $this->allocateFencingToken($provider);
            $context = new IntegrationOperationContext(
                provider: $provider,
                fencingToken: $fencingToken,
                startedAt: $startedAt,
                deadline: $startedAt->addSeconds($this->hardDeadlineSeconds),
            );
            $context->assertWithinDeadline();

            return $operation($context);
        } finally {
            $lock->release();
        }
    }

    public function assertCurrent(IntegrationOperationContext $context, IntegrationSetting $setting): void
    {
        $context->assertWithinDeadline();

        if ($setting->provider !== $context->provider
            || $setting->operation_fence_version !== $context->fencingToken
        ) {
            throw new IntegrationConfigurationException('configuration_changed');
        }
    }

    private function allocateFencingToken(string $provider): int
    {
        return DB::transaction(function () use ($provider): int {
            $setting = IntegrationSetting::query()
                ->where('provider', $provider)
                ->lockForUpdate()
                ->first();

            if ($setting === null) {
                $setting = IntegrationSetting::query()->create(['provider' => $provider]);
            }

            $setting->operation_fence_version++;
            $setting->save();

            return $setting->operation_fence_version;
        });
    }

    private function assertProvider(string $provider): void
    {
        if (! in_array($provider, IntegrationSetting::PROVIDERS, true)) {
            throw new IntegrationConfigurationException('invalid_configuration');
        }
    }

    private function lockName(string $provider): string
    {
        return "sibk:integration:{$provider}:operation";
    }
}
