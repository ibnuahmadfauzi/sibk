<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active
            && $user->hasAnyRole(['guru_bk', 'koordinator_bk']);
    }
}
