<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Correction;
use App\Models\User;

class CorrectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['guru_bk', 'koordinator_bk', 'admin_it']);
    }

    public function view(User $user, Correction $correction): bool
    {
        return Correction::query()->accessibleTo($user)->whereKey($correction->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['guru_bk', 'koordinator_bk']);
    }

    public function verify(User $user, Correction $correction): bool
    {
        return $correction->correction_type === Correction::TYPE_OPERATIONAL
            && $correction->status?->code === 'menunggu'
            && $user->hasRole('koordinator_bk');
    }

    public function processMaster(User $user, Correction $correction): bool
    {
        return $correction->correction_type === Correction::TYPE_MASTER
            && in_array($correction->status?->code, ['menunggu', 'diproses'], true)
            && $user->hasRole('admin_it');
    }
}
