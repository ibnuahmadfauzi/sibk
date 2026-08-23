<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['case_id', 'follow_up_type_id', 'status_id', 'planned_date', 'execution_date', 'result', 'next_plan', 'recorded_by'])]
class FollowUp extends Model
{
    use SoftDeletes;

    /** @return BelongsTo<BkCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(BkCase::class, 'case_id');
    }

    /** @return BelongsTo<ReferenceValue, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'follow_up_type_id');
    }

    /** @return BelongsTo<ReferenceValue, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'status_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return MorphMany<Correction, $this> */
    public function corrections(): MorphMany
    {
        return $this->morphMany(Correction::class, 'target');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'execution_date' => 'date',
        ];
    }
}
