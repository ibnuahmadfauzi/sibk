<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && $user->hasAnyRole(['guru_bk', 'koordinator_bk', 'waka_kesiswaan']);
    }

    public function viewDocument(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole('koordinator_bk')
            || ($user->hasRole('guru_bk') && ! $user->hasRole('waka_kesiswaan'));
    }
}
