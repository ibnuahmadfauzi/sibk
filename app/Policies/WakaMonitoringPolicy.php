<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class WakaMonitoringPolicy
{
    public function viewMonitoring(User $user): bool
    {
        return $user->is_active && $user->hasRole('waka_kesiswaan');
    }

    public function exportMonitoring(User $user): bool
    {
        return $this->viewMonitoring($user);
    }
}
