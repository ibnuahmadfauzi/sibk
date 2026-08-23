<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['source', 'status', 'is_full_snapshot', 'triggered_by', 'received_count', 'processed_count', 'conflict_count', 'summary', 'started_at', 'finished_at'])]
class ExternalSyncRun extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_WARNING = 'warning';

    public const STATUS_FAILED = 'failed';

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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_full_snapshot' => 'boolean',
            'received_count' => 'integer',
            'processed_count' => 'integer',
            'conflict_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
