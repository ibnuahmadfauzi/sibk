<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['registration_number', 'correction_type', 'target_type', 'target_id', 'target_label', 'field_name', 'field_label', 'old_value', 'proposed_value', 'old_value_label', 'proposed_value_label', 'reason', 'status_id', 'requester_id', 'reviewer_id', 'review_notes', 'reviewed_at', 'processed_at', 'external_sync_run_id'])]
class Correction extends Model
{
    use SoftDeletes;

    public const TYPE_OPERATIONAL = 'operational';

    public const TYPE_MASTER = 'master';

    /** @return MorphTo<Model, $this> */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<ReferenceValue, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'status_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return BelongsTo<ExternalSyncRun, $this> */
    public function externalSyncRun(): BelongsTo
    {
        return $this->belongsTo(ExternalSyncRun::class);
    }

    /** @param Builder<Correction> $query */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('koordinator_bk')) {
            return $query;
        }

        return $query->where(function (Builder $access) use ($user): void {
            if ($user->hasRole('guru_bk')) {
                $access->orWhere('requester_id', $user->getKey());
            }

            if ($user->hasRole('admin_it')) {
                $access->orWhere('correction_type', self::TYPE_MASTER);
            }

            if ($user->hasRole('waka_kesiswaan')) {
                $coordinatedCaseIds = BkCase::query()
                    ->whereHas('coordinations', fn (Builder $coordinations): Builder => $coordinations
                        ->where('waka_user_id', $user->getKey()))
                    ->select('cases.id');
                $studentIds = BkCase::query()
                    ->whereIn('id', clone $coordinatedCaseIds)
                    ->whereNotNull('student_id')
                    ->select('student_id');

                $access->orWhere(function (Builder $waka) use ($coordinatedCaseIds, $studentIds): void {
                    $waka->where(function (Builder $cases) use ($coordinatedCaseIds): void {
                        $cases->where('target_type', BkCase::class)->whereIn('target_id', $coordinatedCaseIds);
                    })->orWhere(function (Builder $followUps) use ($coordinatedCaseIds): void {
                        $followUps->where('target_type', FollowUp::class)->whereIn('target_id', FollowUp::query()
                            ->whereIn('case_id', $coordinatedCaseIds)->select('id'));
                    })->orWhere(function (Builder $students) use ($studentIds): void {
                        $students->where('target_type', Student::class)->whereIn('target_id', $studentIds);
                    });
                });
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
