<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['student_id', 'type_id', 'level_id', 'activity_name', 'organizer', 'achievement_date', 'result', 'evidence_reference', 'verification_status_id', 'recorded_by'])]
class Achievement extends Model
{
    use SoftDeletes;

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<ReferenceValue, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'type_id');
    }

    /** @return BelongsTo<ReferenceValue, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'level_id');
    }

    /** @return BelongsTo<ReferenceValue, $this> */
    public function verificationStatus(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'verification_status_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @param Builder<Achievement> $query */
    public function scopeWithinStudentServicePeriod(Builder $query): Builder
    {
        return $query->whereDoesntHave('student.departure', fn (Builder $departures): Builder => $departures
            ->where('status', StudentDeparture::STATUS_OFFICIAL)
            ->whereColumn('student_departures.effective_date', '<=', 'achievements.achievement_date'));
    }

    /** @param Builder<Achievement> $query */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        $query->withinStudentServicePeriod()->whereNotIn('verification_status_id', ReferenceValue::query()
            ->forCategory('achievement_verification_status')->where('code', 'ditolak')->select('id'));

        if ($user->hasAnyRole(['waka_kesiswaan', 'koordinator_bk'])) {
            return $query;
        }

        return $query->where(function (Builder $access) use ($user): void {
            $hasAccess = false;
            if ($user->hasRole('guru_bk')) {
                $access->whereIn('student_id', Student::query()->professionallyAccessibleTo($user)->select('students.id'));
                $hasAccess = true;
            }

            if (! $hasAccess) {
                $access->whereRaw('1 = 0');
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'achievement_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }
}
