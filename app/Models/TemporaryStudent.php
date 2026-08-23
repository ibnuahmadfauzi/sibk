<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nisn', 'input_name', 'created_by', 'reconciliation_status_id', 'reconciled_student_id', 'reconciled_by', 'reconciled_at'])]
class TemporaryStudent extends Model
{
    use SoftDeletes;

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<ReferenceValue, $this> */
    public function reconciliationStatus(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'reconciliation_status_id');
    }

    /** @return BelongsTo<Student, $this> */
    public function reconciledStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'reconciled_student_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /** @return HasMany<IdentityReconciliation, $this> */
    public function reconciliations(): HasMany
    {
        return $this->hasMany(IdentityReconciliation::class);
    }

    /** @return HasMany<BkCase, $this> */
    public function cases(): HasMany
    {
        return $this->hasMany(BkCase::class);
    }

    /** @return HasMany<Consultation, $this> */
    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['reconciled_at' => 'datetime'];
    }
}
