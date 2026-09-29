<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['withdrawal_progress_id', 'progress', 'follow_up_date', 'notes', 'created_by'])]
final class WithdrawalProgressFollowUp extends Model
{
    protected $table = 'withdrawal_progress_follow_ups';

    public function progressLabel(): string
    {
        return WithdrawalProgress::labels()[$this->progress] ?? $this->progress;
    }

    /** @return BelongsTo<WithdrawalProgress, $this> */
    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(WithdrawalProgress::class, 'withdrawal_progress_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['follow_up_date' => 'date'];
    }
}
