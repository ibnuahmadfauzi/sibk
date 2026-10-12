<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'url_hash', 'group_key', 'source_nisn', 'source_name', 'copy_count',
    'is_active', 'approved_by', 'approved_at', 'revoked_by', 'revoked_at',
])]
final class EtatibDuplicateDecision extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'approved_at' => 'datetime', 'revoked_at' => 'datetime'];
    }
}
