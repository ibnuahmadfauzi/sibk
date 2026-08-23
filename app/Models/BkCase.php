<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['registration_number', 'student_id', 'temporary_student_id', 'case_source_id', 'service_field_id', 'status_id', 'service_date', 'referrer', 'initial_info', 'initial_action', 'internal_note', 'final_result', 'resolution_summary', 'continued_plan', 'closed_at', 'created_by'])]
class BkCase extends Model
{
    use SoftDeletes;

    protected $table = 'cases';

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

    /** @return BelongsTo<ReferenceValue, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'case_source_id');
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<CaseAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(CaseAssignment::class, 'case_id');
    }

    /** @return HasMany<CaseCoordination, $this> */
    public function coordinations(): HasMany
    {
        return $this->hasMany(CaseCoordination::class, 'case_id');
    }

    /** @return HasMany<FollowUp, $this> */
    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class, 'case_id');
    }

    /** @return HasMany<Consultation, $this> */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'case_id');
    }

    /** @return MorphMany<Correction, $this> */
    public function corrections(): MorphMany
    {
        return $this->morphMany(Correction::class, 'target');
    }

    /** @return BelongsToMany<ExternalTatibRecord, $this> */
    public function etatibRecords(): BelongsToMany
    {
        return $this->belongsToMany(ExternalTatibRecord::class, 'case_etatib_links', 'case_id', 'external_tatib_record_id')
            ->withPivot(['linked_by', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    /** @param Builder<BkCase> $query */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('koordinator_bk')) {
            return $query;
        }

        if (! $user->hasAnyRole(['guru_bk', 'waka_kesiswaan'])) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $access) use ($user): void {
            if ($user->hasRole('guru_bk')) {
                $access->whereHas('assignments', fn (Builder $assignments): Builder => $assignments
                    ->where('user_id', $user->getKey())
                    ->effectiveOn(now()))
                    ->orWhereIn('student_id', Student::query()
                        ->forActiveTeacherAssignment($user, now())
                        ->select('students.id'));
            }

            if ($user->hasRole('waka_kesiswaan')) {
                $access->orWhereHas('coordinations', fn (Builder $coordinations): Builder => $coordinations
                    ->where('waka_user_id', $user->getKey()));
            }
        });
    }

    public function identityName(): string
    {
        return $this->student?->name ?? $this->temporaryStudent?->input_name ?? 'Identitas tidak tersedia';
    }

    public function identityNisn(): string
    {
        return $this->student?->nisn ?? $this->temporaryStudent?->nisn ?? '';
    }

    public function hasActiveAssignmentFor(User $user): bool
    {
        return $this->assignments()
            ->where('user_id', $user->getKey())
            ->effectiveOn(now())
            ->exists();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'closed_at' => 'date',
        ];
    }
}
