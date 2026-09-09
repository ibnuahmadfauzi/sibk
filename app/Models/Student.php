<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['dapodik_id', 'nisn', 'name', 'is_active', 'synced_at', 'master_source', 'source_confirmed_at'])]
class Student extends Model
{
    public const MASTER_SOURCE_SCHOOL_PROVISIONAL = 'school_provisional';

    public const MASTER_SOURCE_DAPODIK = 'dapodik';

    public const MASTER_SOURCE_LEGACY_UNCLASSIFIED = 'legacy_unclassified';

    /** @return HasMany<StudentClassMembership, $this> */
    public function classMemberships(): HasMany
    {
        return $this->hasMany(StudentClassMembership::class);
    }

    /** @return HasMany<TemporaryStudent, $this> */
    public function temporaryIdentities(): HasMany
    {
        return $this->hasMany(TemporaryStudent::class, 'reconciled_student_id');
    }

    /** @return HasMany<BkCase, $this> */
    public function cases(): HasMany
    {
        return $this->hasMany(BkCase::class);
    }

    /** @return HasMany<Consultation, $this> */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    /** @return HasMany<Achievement, $this> */
    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    /** @return MorphMany<Correction, $this> */
    public function corrections(): MorphMany
    {
        return $this->morphMany(Correction::class, 'target');
    }

    /** @param Builder<Student> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<Student> $query */
    public function scopeForActiveTeacherAssignment(
        Builder $query,
        User $teacher,
        CarbonInterface|string $date,
    ): Builder {
        return $query->whereExists(function ($scope) use ($teacher, $date): void {
            $scope->selectRaw('1')
                ->from('student_class_memberships as memberships')
                ->join('teacher_assignments as assignments', function ($join): void {
                    $join->on('assignments.classroom_id', '=', 'memberships.classroom_id')
                        ->on('assignments.academic_year_id', '=', 'memberships.academic_year_id');
                })
                ->join('academic_years as years', 'years.id', '=', 'memberships.academic_year_id')
                ->whereColumn('memberships.student_id', 'students.id')
                ->where('memberships.is_active', true)
                ->where('years.is_active', true)
                ->where('assignments.user_id', $teacher->getKey())
                ->whereNull('assignments.deleted_at')
                ->whereDate('memberships.effective_from', '<=', $date)
                ->where(function ($period) use ($date): void {
                    $period->whereNull('memberships.effective_until')
                        ->orWhereDate('memberships.effective_until', '>=', $date);
                })
                ->whereDate('assignments.effective_from', '<=', $date)
                ->where(function ($period) use ($date): void {
                    $period->whereNull('assignments.effective_until')
                        ->orWhereDate('assignments.effective_until', '>=', $date);
                })
                ->where(function ($period) use ($date): void {
                    $period->whereNull('years.starts_on')
                        ->orWhereDate('years.starts_on', '<=', $date);
                })
                ->where(function ($period) use ($date): void {
                    $period->whereNull('years.ends_on')
                        ->orWhereDate('years.ends_on', '>=', $date);
                });
        });
    }

    /** @param Builder<Student> $query */
    public function scopeProfessionallyAccessibleTo(Builder $query, User $teacher): Builder
    {
        if (! $teacher->hasRole('guru_bk')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $access) use ($teacher): void {
            $access->whereIn('students.id', Student::query()
                ->forActiveTeacherAssignment($teacher, now())
                ->select('students.id'))
                ->orWhereHas('cases.assignments', fn (Builder $assignments): Builder => $assignments
                    ->where('user_id', $teacher->getKey())
                    ->effectiveOn(now()));
        });
    }

    /** @param Builder<Student> $query */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('koordinator_bk')) {
            return $query;
        }

        if ($user->hasRole('guru_bk')) {
            return $query->professionallyAccessibleTo($user);
        }

        if ($user->hasRole('waka_kesiswaan')) {
            return $query->whereHas('cases.coordinations', fn (Builder $coordinations): Builder => $coordinations
                ->where('waka_user_id', $user->getKey()));
        }

        return $query->whereRaw('1 = 0');
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
