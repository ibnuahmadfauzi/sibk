<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['registration_number', 'student_id', 'temporary_student_id', 'case_id', 'service_field_id', 'status_id', 'topic', 'referral_source', 'session_date', 'starts_at', 'ends_at', 'follow_up_date', 'general_summary', 'counselor_id'])]
class Consultation extends Model
{
    use SoftDeletes;

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<TemporaryStudent, $this> */
    public function temporaryStudent(): BelongsTo
    {
        return $this->belongsTo(TemporaryStudent::class);
    }

    /** @return BelongsTo<BkCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(BkCase::class, 'case_id');
    }

    /** @return BelongsTo<ReferenceValue, $this> */
    public function serviceField(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'service_field_id');
    }

    /** @return BelongsTo<ReferenceValue, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'status_id');
    }

    /** @return BelongsTo<User, $this> */
    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    /** @return HasOne<ConsultationPrivateNote, $this> */
    public function privateNote(): HasOne
    {
        return $this->hasOne(ConsultationPrivateNote::class);
    }

    /** @return MorphMany<Correction, $this> */
    public function corrections(): MorphMany
    {
        return $this->morphMany(Correction::class, 'target');
    }

    /** @param Builder<Consultation> $query */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('koordinator_bk')) {
            return $query;
        }

        if (! $user->hasRole('guru_bk')) {
            return $query->whereRaw('1 = 0');
        }

        $studentIds = Student::query()->professionallyAccessibleTo($user)->select('students.id');

        return $query->where(function (Builder $access) use ($user, $studentIds): void {
            $access->whereIn('student_id', $studentIds)
                ->orWhereHas('temporaryStudent', fn (Builder $temporary): Builder => $temporary
                    ->whereIn('reconciled_student_id', Student::query()
                        ->professionallyAccessibleTo($user)
                        ->select('students.id')))
                ->orWhereHas('case.assignments', fn (Builder $assignments): Builder => $assignments
                    ->where('user_id', $user->getKey())
                    ->effectiveOn(now()))
                ->orWhere(function (Builder $pending) use ($user): void {
                    $pending->where('counselor_id', $user->getKey())
                        ->whereHas('temporaryStudent', fn (Builder $temporary): Builder => $temporary
                            ->whereNull('reconciled_student_id'));
                });
        });
    }

    public function isProfessionallyAccessibleTo(User $user): bool
    {
        if (! $user->hasRole('guru_bk')) {
            return false;
        }

        if ($this->student_id !== null) {
            return Student::query()->professionallyAccessibleTo($user)->whereKey($this->student_id)->exists();
        }

        if ($this->case_id !== null && $this->case?->hasActiveAssignmentFor($user)) {
            return true;
        }

        $temporary = $this->temporaryStudent()->first();
        if ($temporary?->reconciled_student_id !== null) {
            return Student::query()
                ->professionallyAccessibleTo($user)
                ->whereKey($temporary->reconciled_student_id)
                ->exists();
        }

        return $temporary !== null && $this->counselor_id === $user->getKey();
    }

    public function identityName(): string
    {
        return $this->student?->name
            ?? $this->temporaryStudent?->reconciledStudent?->name
            ?? $this->temporaryStudent?->input_name
            ?? 'Identitas tidak tersedia';
    }

    public function identityNisn(): string
    {
        return $this->student?->nisn ?? $this->temporaryStudent?->nisn ?? '';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'follow_up_date' => 'date',
        ];
    }
}
