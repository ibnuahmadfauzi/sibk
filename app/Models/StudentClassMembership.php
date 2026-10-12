<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dapodik_id', 'student_id', 'classroom_id', 'academic_year_id', 'is_active', 'synced_at', 'master_source', 'source_confirmed_at'])]
class StudentClassMembership extends Model
{
    public const MASTER_SOURCE_SCHOOL_PROVISIONAL = 'school_provisional';

    public const MASTER_SOURCE_DAPODIK = 'dapodik';

    public const MASTER_SOURCE_LEGACY_UNCLASSIFIED = 'legacy_unclassified';

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @param Builder<StudentClassMembership> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<StudentClassMembership> $query */
    public function scopeInActiveYear(Builder $query): Builder
    {
        return $query->active()->whereHas('academicYear', fn (Builder $years): Builder => $years->where('is_active', true));
    }

    /** @param Builder<StudentClassMembership> $query */
    public function scopeLatestYearFirst(Builder $query): Builder
    {
        return $query->orderByDesc(
            AcademicYear::query()
                ->select('starts_on')
                ->whereColumn('academic_years.id', 'student_class_memberships.academic_year_id')
                ->limit(1),
        )->orderByDesc('student_class_memberships.id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
            'source_confirmed_at' => 'datetime',
        ];
    }
}
