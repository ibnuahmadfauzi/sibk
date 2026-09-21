<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Consultation;
use App\Models\User;
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
    public function forRecord(BkCase|Consultation $record): array
    {
        $teacher = $record instanceof BkCase
            ? $record->assignments
                ->where('assignment_type', CaseAssignment::TYPE_OWNER)
                ->sortByDesc(fn (CaseAssignment $assignment): string => sprintf(
                    '%s-%010d',
                    $assignment->effective_from->format('Y-m-d'),
                    $assignment->id,
                ))
                ->first()?->teacher
            : $record->counselor;

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
