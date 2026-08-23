<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['student_id', 'type_id', 'level_id', 'activity_name', 'organizer', 'achievement_date', 'result', 'evidence_reference', 'evidence_description', 'notes', 'verification_status_id', 'recorded_by', 'reviewer_id', 'reviewed_at', 'verification_notes'])]
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

    /** @return MorphMany<Correction, $this> */
    public function corrections(): MorphMany
    {
        return $this->morphMany(Correction::class, 'target');
    }

    /** @param Builder<Achievement> $query */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('koordinator_bk')) {
            return $query;
        }

        return $query->where(function (Builder $access) use ($user): void {
            $hasAccess = false;
            if ($user->hasRole('guru_bk')) {
                $access->whereIn('student_id', Student::query()->professionallyAccessibleTo($user)->select('students.id'));
                $hasAccess = true;
            }

            if ($user->hasRole('waka_kesiswaan')) {
                $method = $hasAccess ? 'orWhere' : 'where';
                $verifiedStatus = ReferenceValue::query()
                    ->forCategory('achievement_verification_status')
                    ->where('code', 'terverifikasi')
                    ->select('id');
                $coordinatedStudents = Student::query()
                    ->whereHas('cases.coordinations', fn (Builder $coordinations): Builder => $coordinations
                        ->where('waka_user_id', $user->getKey()))
                    ->select('students.id');
                $access->{$method}(function (Builder $waka) use ($verifiedStatus, $coordinatedStudents): void {
                    $waka->whereIn('verification_status_id', $verifiedStatus)
                        ->whereIn('student_id', $coordinatedStudents);
                });
                $hasAccess = true;
            }

            if (! $hasAccess) {
                $access->whereRaw('1 = 0');
            }
        });
    }

    public function isPending(): bool
    {
        return $this->verificationStatus?->code === 'menunggu';
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
