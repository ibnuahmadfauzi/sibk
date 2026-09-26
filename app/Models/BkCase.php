<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['registration_number', 'student_id', 'temporary_student_id', 'academic_year_id', 'classroom_id', 'case_source_id', 'service_field_id', 'status_id', 'follow_up_type_id', 'service_date', 'referrer', 'initial_info', 'initial_action', 'internal_note', 'resolution_summary', 'closed_at', 'created_by'])]
class BkCase extends Model
{
    use SoftDeletes;

    protected $table = 'cases';

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

    /** @return BelongsTo<ReferenceValue, $this> */
    public function followUpType(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'follow_up_type_id');
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

    /** @return HasMany<Consultation, $this> */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'case_id');
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
    public function scopeWithinStudentServicePeriod(Builder $query): Builder
    {
        return $query->whereDoesntHave('student.departure', fn (Builder $departures): Builder => $departures
            ->where('status', StudentDeparture::STATUS_OFFICIAL)
            ->whereColumn('student_departures.effective_date', '<=', 'cases.service_date'));
    }

    /** @param Builder<BkCase> $query */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        $query->withinStudentServicePeriod();

        if ($user->hasAnyRole(['koordinator_bk', 'waka_kesiswaan'])) {
            return $query;
        }

        if (! $user->hasRole('guru_bk')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $access) use ($user): void {
            if ($user->hasRole('guru_bk')) {
                $access->whereHas('assignments', fn (Builder $assignments): Builder => $assignments
                    ->where('user_id', $user->getKey()))
                    ->orWhereIn('student_id', Student::query()
                        ->forActiveTeacherAssignment($user)
                        ->select('students.id'));
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

    public function isOwnedBy(User $user): bool
    {
        return $this->assignments()
            ->where('user_id', $user->getKey())
            ->exists();
    }

    public function ownerAssignment(): ?CaseAssignment
    {
        return $this->assignments()
            ->first();
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
