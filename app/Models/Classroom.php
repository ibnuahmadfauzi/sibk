<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['dapodik_id', 'academic_year_id', 'name', 'grade_level', 'major', 'is_active', 'synced_at', 'master_source', 'source_confirmed_at'])]
class Classroom extends Model
{
    public const MASTER_SOURCE_SCHOOL_PROVISIONAL = 'school_provisional';

    public const MASTER_SOURCE_DAPODIK = 'dapodik';

    public const MASTER_SOURCE_LEGACY_UNCLASSIFIED = 'legacy_unclassified';

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return HasMany<StudentClassMembership, $this> */
    public function studentClassMemberships(): HasMany
    {
        return $this->hasMany(StudentClassMembership::class);
    }

    /** @return HasMany<TeacherAssignment, $this> */
    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /** @param Builder<Classroom> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
            'source_confirmed_at' => 'datetime',
        ];
    }
}
