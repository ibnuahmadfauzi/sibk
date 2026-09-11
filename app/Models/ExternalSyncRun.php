<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'source',
    'status',
    'is_full_snapshot',
    'triggered_by',
    'received_count',
    'processed_count',
    'conflict_count',
    'summary',
    'started_at',
    'finished_at',
    'snapshot_fingerprint',
    'snapshot_evidence',
    'deactivation_plan',
    'preview_generation',
    'decision_revision',
    'configuration_version',
    'preview_fencing_token',
    'driver_id',
    'adapter_version',
    'contract_version',
    'endpoint_policy_digest',
    'preview_expires_at',
    'applied_at',
    'superseded_at',
])]
class ExternalSyncRun extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_WARNING = 'warning';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PREVIEW_READY = 'preview_ready';

    public const STATUS_SUPERSEDED = 'superseded';

    /** @return BelongsTo<User, $this> */
    public function trigger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /** @return HasMany<ExternalSyncIssue, $this> */
    public function issues(): HasMany
    {
        return $this->hasMany(ExternalSyncIssue::class);
    }

    /** @return HasMany<DapodikSyncPreviewItem, $this> */
    public function previewItems(): HasMany
    {
        return $this->hasMany(DapodikSyncPreviewItem::class);
    }

    /** @return HasMany<DapodikSyncPreviewItem, $this> */
    public function items(): HasMany
    {
        return $this->previewItems();
    }

    protected static function booted(): void
    {
        static::updating(function (self $run): void {
            if ($run->getOriginal('source') !== 'dapodik'
                || $run->getOriginal('snapshot_fingerprint') === null
            ) {
                return;
            }

            foreach ([
                'is_full_snapshot',
                'received_count',
                'snapshot_fingerprint',
                'snapshot_evidence',
                'deactivation_plan',
                'preview_generation',
                'configuration_version',
                'preview_fencing_token',
                'driver_id',
                'adapter_version',
                'contract_version',
                'endpoint_policy_digest',
                'preview_expires_at',
            ] as $immutableAttribute) {
                if ($run->isDirty($immutableAttribute)) {
                    throw new \LogicException('Metadata pratinjau Dapodik tidak dapat diubah.');
                }
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_full_snapshot' => 'boolean',
            'snapshot_evidence' => 'array',
            'deactivation_plan' => 'array',
            'received_count' => 'integer',
            'processed_count' => 'integer',
            'conflict_count' => 'integer',
            'preview_generation' => 'integer',
            'decision_revision' => 'integer',
            'configuration_version' => 'integer',
            'preview_fencing_token' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'preview_expires_at' => 'datetime',
            'applied_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }
}
