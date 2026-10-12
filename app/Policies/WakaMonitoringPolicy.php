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

    public function viewReportTab(User $user, string $tab): bool
    {
        if (! $user->is_active || ! in_array($tab, ['penanganan', 'rekap', 'laporan-akhir'], true)) {
            return false;
        }

        return $user->hasRole('waka_kesiswaan')
            || ($tab === 'laporan-akhir' && $user->hasRole('koordinator_bk'));
    }
}
