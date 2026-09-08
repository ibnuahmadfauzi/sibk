<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['dapodik_id', 'student_id', 'classroom_id', 'academic_year_id', 'effective_from', 'effective_until', 'is_active', 'synced_at'])]
class StudentClassMembership extends Model
{
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
    public function scopeEffectiveOn(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query
            ->whereDate('effective_from', '<=', $date)
            ->where(fn (Builder $builder): Builder => $builder
                ->whereNull('effective_until')
                ->orWhereDate('effective_until', '>=', $date))
            ->whereHas('academicYear', fn (Builder $academicYear): Builder => $academicYear
                ->where(fn (Builder $periodStart): Builder => $periodStart
                    ->whereNull('starts_on')
                    ->orWhereDate('starts_on', '<=', $date))
                ->where(fn (Builder $periodEnd): Builder => $periodEnd
                    ->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $date)));
    }

    /** @param Builder<StudentClassMembership> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<StudentClassMembership> $query */
    public function scopeActiveOn(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query->active()->effectiveOn($date);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }
}
