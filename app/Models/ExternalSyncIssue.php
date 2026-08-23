<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['external_sync_run_id', 'entity_type', 'source_identifier', 'nisn', 'input_name', 'issue_code', 'summary', 'details', 'resolved_student_id', 'resolved_by', 'resolved_at'])]
class ExternalSyncIssue extends Model
{
    /** @return BelongsTo<ExternalSyncRun, $this> */
    public function syncRun(): BelongsTo
    {
        return $this->belongsTo(ExternalSyncRun::class, 'external_sync_run_id');
    }

    /** @return BelongsTo<Student, $this> */
    public function resolvedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'resolved_student_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'details' => 'array',
            'resolved_at' => 'datetime',
        ];
    }
}
