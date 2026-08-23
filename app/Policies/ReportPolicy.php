<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(['guru_bk', 'koordinator_bk', 'waka_kesiswaan']);
    }

    public function viewType(User $user, string $type): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($type === 'konsultasi') {
            return $user->hasAnyRole(['guru_bk', 'koordinator_bk']);
        }

        return true;
    }

    public function export(User $user, string $type): bool
    {
        return $this->viewType($user, $type);
    }
}
