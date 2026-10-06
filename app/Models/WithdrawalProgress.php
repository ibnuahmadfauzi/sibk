<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'teacher_id', 'classroom_id', 'recorded_on', 'progress', 'note', 'reason'])]
final class WithdrawalProgress extends Model
{
    protected $table = 'withdrawal_progresses';

    public const string PROGRESS_IN_PROGRESS = 'in_progress';

    public const string PROGRESS_AT_BK = 'at_bk';

    public const string PROGRESS_AT_TU = 'at_tu';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::PROGRESS_IN_PROGRESS => 'Berkas masih diproses',
            self::PROGRESS_AT_BK => 'Berkas masih di BK',
            self::PROGRESS_AT_TU => 'Berkas sudah masuk TU',
        ];
    }

    public function progressLabel(): string
    {
        return self::labels()[$this->progress] ?? $this->progress;
    }

    public function identityName(): string
    {
        return \App\Support\StudentName::display($this->student?->name ?? 'Identitas tidak tersedia');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return HasMany<WithdrawalProgressFollowUp, $this> */
    public function followUps(): HasMany
    {
        return $this->hasMany(WithdrawalProgressFollowUp::class)
            ->orderByDesc('follow_up_date')
            ->orderByDesc('id');
    }

    /** @param Builder<WithdrawalProgress> $query */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('koordinator_bk')) {
            return $query;
        }

        if ($user->hasRole('guru_bk')) {
            return $query->whereIn('student_id', Student::query()
                ->professionallyAccessibleTo($user)
                ->select('students.id'));
        }

        return $query->whereRaw('1 = 0');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['recorded_on' => 'date'];
    }
}
