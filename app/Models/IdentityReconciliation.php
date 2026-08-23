<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['temporary_student_id', 'source_nisn', 'student_id', 'official_name', 'status_id', 'result', 'checked_by', 'conflict_details', 'checked_at'])]
class IdentityReconciliation extends Model
{
    /** @return BelongsTo<TemporaryStudent, $this> */
    public function temporaryStudent(): BelongsTo
    {
        return $this->belongsTo(TemporaryStudent::class);
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<ReferenceValue, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(ReferenceValue::class, 'status_id');
    }

    /** @return BelongsTo<User, $this> */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'conflict_details' => 'array',
            'checked_at' => 'datetime',
        ];
    }
}
