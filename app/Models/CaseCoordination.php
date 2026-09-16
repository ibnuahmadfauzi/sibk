<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['case_id', 'waka_user_id', 'status_id', 'coordination_need', 'result', 'recorded_by', 'coordinated_at'])]
class CaseCoordination extends Model
{
    use SoftDeletes;

    /** @return BelongsTo<BkCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(BkCase::class, 'case_id');
    }

    /** @return BelongsTo<User, $this> */
    public function waka(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waka_user_id');
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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['coordinated_at' => 'datetime'];
    }
}
