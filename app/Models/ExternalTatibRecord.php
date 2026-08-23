<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['source_identifier', 'nisn', 'student_id', 'occurred_at', 'violation_type', 'category', 'points', 'source_status', 'is_active', 'source_synced_at', 'synced_at'])]
class ExternalTatibRecord extends Model
{
    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsToMany<BkCase, $this> */
    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(BkCase::class, 'case_etatib_links', 'external_tatib_record_id', 'case_id')
            ->withPivot(['linked_by', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    /** @param Builder<ExternalTatibRecord> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'points' => 'integer',
            'is_active' => 'boolean',
            'source_synced_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
