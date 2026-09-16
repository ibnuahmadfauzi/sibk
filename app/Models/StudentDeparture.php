<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id', 'departure_type', 'status', 'reported_at',
    'recommendation_summary', 'effective_date', 'recorded_by',
    'finalized_by', 'finalized_at', 'decision_note',
])]
final class StudentDeparture extends Model
{
    public const string STATUS_IN_PROGRESS = 'dalam_proses';

    public const string STATUS_CANCELLED = 'batal';

    public const string STATUS_OFFICIAL = 'resmi_keluar';

    public const string TYPE_GRADUATED = 'lulus';

    public const string TYPE_TRANSFER = 'pindah';

    public const string TYPE_WITHDRAWAL = 'mengundurkan_diri';

    public const string TYPE_OTHER = 'keluar_lainnya';

    /** @return list<string> */
    public static function types(): array
    {
        return [self::TYPE_GRADUATED, self::TYPE_TRANSFER, self::TYPE_WITHDRAWAL, self::TYPE_OTHER];
    }

    public function typeLabel(): string
    {
        return match ($this->departure_type) {
            self::TYPE_GRADUATED => 'Lulus',
            self::TYPE_TRANSFER => 'Pindah',
            self::TYPE_WITHDRAWAL => 'Mengundurkan diri',
            default => 'Keluar lainnya',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'Dalam proses',
            self::STATUS_CANCELLED => 'Batal',
            default => 'Resmi keluar',
        };
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return BelongsTo<User, $this> */
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reported_at' => 'date',
            'effective_date' => 'date',
            'finalized_at' => 'datetime',
        ];
    }
}
