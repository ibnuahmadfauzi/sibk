<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['case_id', 'follow_up_type_id', 'follow_up_date', 'notes', 'created_by'])]
class CaseFollowUp extends Model
{
    use SoftDeletes;

    protected $table = 'case_follow_ups';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
        ];
    }

    /** @return BelongsTo<BkCase, $this> */
    public function case(): BelongsTo
    {
        return $this->belongsTo(BkCase::class, 'case_id');
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
}
