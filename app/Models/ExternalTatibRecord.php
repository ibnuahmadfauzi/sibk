<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\SyncIssueReviewService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'source_identifier',
    'nisn',
    'source_nisn',
    'student_id',
    'source_student_name',
    'source_classroom_name',
    'occurred_at',
    'violation_type',
    'category',
    'recorded_by_name',
    'points',
    'source_total_points',
    'source_status',
    'is_active',
    'source_synced_at',
    'source_deleted_at',
    'synced_at',
])]
class ExternalTatibRecord extends Model
{
    /** @return HasOne<ExternalSyncIssue, $this> */
    public function latestClassroomIssue(): HasOne
    {
        return $this->hasOne(ExternalSyncIssue::class, 'source_identifier', 'source_identifier')
            ->ofMany(['id' => 'max'], fn ($issues) => $issues->where('issue_code', 'student_classroom_mismatch'));
    }

    public function getEffectiveClassroomNameAttribute(): ?string
    {
        $issue = $this->latestClassroomIssue;
        if ($issue?->resolved_at !== null
            && in_array(data_get($issue->details, 'review.action'), ['use_school', 'use_etatib'], true)
            && $this->student_id !== null && $this->occurred_at !== null && $this->source_classroom_name !== null) {
            $date = $this->occurred_at->toDateString();
            if ($this->relationLoaded('student') && $this->student?->relationLoaded('classMemberships')) {
                $classes = $this->student->classMemberships
                    ->filter(fn (StudentClassMembership $membership): bool => $membership->academicYear !== null
                        && $membership->academicYear->starts_on !== null
                        && $membership->academicYear->ends_on !== null
                        && $membership->academicYear->starts_on->toDateString() <= $date
                        && $membership->academicYear->ends_on->toDateString() >= $date)
                    ->pluck('classroom.name')->filter()->all();
            } else {
                $classes = StudentClassMembership::query()->where('student_id', $this->student_id)->active()
                    ->whereHas('academicYear', fn ($years) => $years->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date))
                    ->with('classroom:id,name')->get()->pluck('classroom.name')->filter()->all();
            }
            if (data_get($issue->details, 'review.choice.signature') === SyncIssueReviewService::classroomSignature(
                $this->student_id, $date, $this->source_classroom_name, $classes,
            )) {
                return data_get($issue->details, 'review.choice.classroom') ?: $this->source_classroom_name;
            }
        }

        return $this->source_classroom_name;
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsToMany<BkCase, $this> */
    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(BkCase::class, 'case_etatib_links', 'external_tatib_record_id', 'case_id')
            ->withPivot(['linked_by', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    /** @param Builder<ExternalTatibRecord> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'points' => 'integer',
            'source_total_points' => 'integer',
            'is_active' => 'boolean',
            'source_synced_at' => 'datetime',
            'source_deleted_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
