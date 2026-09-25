<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'provider',
    'base_url',
    'expected_source_identifier',
    'credentials',
    'timeout_seconds',
    'configuration_version',
    'operation_fence_version',
    'verified_configuration_version',
    'verified_driver_id',
    'verified_adapter_version',
    'verified_contract_version',
    'verified_endpoint_policy_digest',
    'last_test_status',
    'last_test_code',
    'last_tested_at',
    'last_tested_by',
    'is_enabled',
    'automatic_sync_url',
    'automatic_sync_enabled',
    'automatic_sync_enabled_at',
    'automatic_sync_updated_by',
    'sync_watermark',
    'last_full_synced_at',
    'last_successful_sync_at',
    'last_probe_summary',
    'updated_by',
])]
#[Hidden(['credentials', 'automatic_sync_url'])]
class IntegrationSetting extends Model
{
    public const string PROVIDER_DAPODIK = 'dapodik';

    public const string PROVIDER_ETATIB = 'etatib';

    /** @var list<string> */
    public const array PROVIDERS = [
        self::PROVIDER_DAPODIK,
        self::PROVIDER_ETATIB,
    ];

    public const string TEST_STATUS_UNTESTED = 'untested';

    public const string TEST_STATUS_SUCCESS = 'success';

    public const string TEST_STATUS_FAILED = 'failed';

    /** @return BelongsTo<User, $this> */
    public function lastTestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_tested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function automaticSyncUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'automatic_sync_updated_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'timeout_seconds' => 'integer',
            'configuration_version' => 'integer',
            'operation_fence_version' => 'integer',
            'verified_configuration_version' => 'integer',
            'last_tested_at' => 'datetime',
            'is_enabled' => 'boolean',
            'automatic_sync_url' => 'encrypted',
            'automatic_sync_enabled' => 'boolean',
            'automatic_sync_enabled_at' => 'datetime',
            'last_full_synced_at' => 'datetime',
            'last_successful_sync_at' => 'datetime',
            'last_probe_summary' => 'array',
        ];
    }
}
