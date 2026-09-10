<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'external_sync_run_id',
    'entity_type',
    'source_identifier',
    'candidate_id',
    'match_status',
    'safe_fields',
    'item_hash',
    'target_fingerprint',
    'preview_generation',
    'decision_revision',
    'decision',
    'decision_candidate_id',
    'decision_target_fingerprint',
    'decided_by',
    'decided_at',
])]
class DapodikSyncPreviewItem extends Model
{
    public const string ENTITY_ACADEMIC_YEAR = 'academic_year';

    public const string ENTITY_CLASSROOM = 'classroom';

    public const string ENTITY_STUDENT = 'student';

    public const string ENTITY_MEMBERSHIP = 'membership';

    /** @var list<string> */
    public const array ENTITY_TYPES = [
        self::ENTITY_ACADEMIC_YEAR,
        self::ENTITY_CLASSROOM,
        self::ENTITY_STUDENT,
        self::ENTITY_MEMBERSHIP,
    ];

    public const string MATCH_EXACT = 'exact_match';

    public const string MATCH_NEW = 'new_record';

    public const string MATCH_CHANGED = 'changed';

    public const string MATCH_NEEDS_MAPPING = 'needs_mapping';

    public const string MATCH_CONFLICT = 'conflict';

    public const string DECISION_MAP_EXISTING = 'map_existing';

    public const string DECISION_CREATE_NEW = 'create_new';

    /** @return BelongsTo<ExternalSyncRun, $this> */
    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(ExternalSyncRun::class, 'external_sync_run_id');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    protected static function booted(): void
    {
        static::updating(function (self $item): void {
            foreach ([
                'external_sync_run_id',
                'entity_type',
                'source_identifier',
                'candidate_id',
                'match_status',
                'safe_fields',
                'item_hash',
                'target_fingerprint',
                'preview_generation',
            ] as $immutableAttribute) {
                if ($item->isDirty($immutableAttribute)) {
                    throw new LogicException('Snapshot pratinjau Dapodik tidak dapat diubah.');
                }
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'candidate_id' => 'integer',
            'safe_fields' => 'array',
            'preview_generation' => 'integer',
            'decision_revision' => 'integer',
            'decision_candidate_id' => 'integer',
            'decided_at' => 'datetime',
        ];
    }
}
