<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['dapodik_id', 'name', 'starts_on', 'ends_on', 'is_active', 'synced_at'])]
class AcademicYear extends Model
{
    /** @return HasMany<Classroom, $this> */
    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
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

    /** @param Builder<AcademicYear> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }
}
