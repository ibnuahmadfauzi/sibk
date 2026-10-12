<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\User;
use App\Models\WithdrawalProgress;
use Illuminate\Database\Eloquent\Builder;

final class ReportSignatoryResolver
{
    /** @return array{left: array{role: string, name: string}, right: array{role: string, name: string}} */
    public function forRecap(): array
    {
        return [
            'left' => $this->uniqueActiveRole('waka_kesiswaan', 'Waka Kesiswaan'),
            'right' => $this->uniqueActiveRole('koordinator_bk', 'Koordinator BK'),
        ];
    }

    /** @return array{left: array{role: string, name: string}, right: array{role: string, name: string}} */
    public function forRecord(BkCase|Consultation|WithdrawalProgress $record): array
    {
        $teacher = match (true) {
            $record instanceof BkCase => $record->assignments->first()?->teacher,
            $record instanceof Consultation => $record->counselor,
            $record instanceof WithdrawalProgress => $record->teacher,
        };

        return [
            'left' => $this->uniqueActiveRole('waka_kesiswaan', 'Waka Kesiswaan'),
            'right' => [
                'role' => 'Guru BK Penanggung Jawab',
                'name' => $teacher?->name ?? 'Penandatangan belum tersedia',
            ],
        ];
    }

    /** @return array{role: string, name: string} */
    private function uniqueActiveRole(string $role, string $label): array
    {
        $users = User::query()
            ->active()
            ->whereHas('roles', fn (Builder $roles): Builder => $roles
                ->where('slug', $role)
                ->where('is_active', true))
            ->limit(2)
            ->get(['id', 'name']);

        return [
            'role' => $label,
            'name' => $users->count() === 1
                ? $users->first()->name
                : 'Penandatangan belum tersedia',
        ];
    }
}
