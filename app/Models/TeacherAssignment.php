<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'classroom_id', 'academic_year_id', 'effective_from', 'effective_until', 'decision_number', 'notes', 'assigned_by'])]
class TeacherAssignment extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ENDED = 'ended';

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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

    /** @return BelongsTo<User, $this> */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /** @param Builder<TeacherAssignment> $query */
    public function scopeEffectiveOn(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query
            ->whereDate('effective_from', '<=', $date)
            ->where(fn (Builder $period): Builder => $period
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

    /** @param Builder<TeacherAssignment> $query */
    public function scopeActiveOn(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query->effectiveOn($date);
    }

    /** @param Builder<TeacherAssignment> $query */
    public function scopeScheduledOn(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query
            ->where(fn (Builder $period): Builder => $period
                ->whereNull('effective_until')
                ->orWhereDate('effective_until', '>=', $date))
            ->whereHas('academicYear', fn (Builder $academicYear): Builder => $academicYear
                ->where(fn (Builder $periodEnd): Builder => $periodEnd
                    ->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $date)))
            ->where(fn (Builder $schedule): Builder => $schedule
                ->whereDate('effective_from', '>', $date)
                ->orWhereHas('academicYear', fn (Builder $academicYear): Builder => $academicYear
                    ->whereDate('starts_on', '>', $date)));
    }

    /** @param Builder<TeacherAssignment> $query */
    public function scopeEndedOn(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query->where(fn (Builder $ended): Builder => $ended
            ->whereDate('effective_until', '<', $date)
            ->orWhereHas('academicYear', fn (Builder $academicYear): Builder => $academicYear
                ->whereDate('ends_on', '<', $date)));
    }

    public function statusOn(CarbonInterface|string $date): string
    {
        $on = CarbonImmutable::parse($date)->startOfDay();

        if ($this->effectiveEnd()?->lt($on)) {
            return self::STATUS_ENDED;
        }

        if ($this->effective_from->gt($on) || $this->academicYear?->starts_on?->gt($on)) {
            return self::STATUS_SCHEDULED;
        }

        return self::STATUS_ACTIVE;
    }

    public function effectiveEnd(): ?CarbonInterface
    {
        $assignmentEnd = $this->effective_until;
        $academicYearEnd = $this->academicYear?->ends_on;

        if ($assignmentEnd === null) {
            return $academicYearEnd;
        }

        if ($academicYearEnd === null) {
            return $assignmentEnd;
        }

        return $assignmentEnd->lte($academicYearEnd) ? $assignmentEnd : $academicYearEnd;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }
}
