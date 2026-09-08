<?php

declare(strict_types=1);

namespace App\Models;

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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }
}
