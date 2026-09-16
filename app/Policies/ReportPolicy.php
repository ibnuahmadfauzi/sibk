<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\OperationalReportRecapService;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(['guru_bk', 'koordinator_bk']);
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

    public function viewTab(User $user, string $tab): bool
    {
        return $this->viewAny($user)
            && in_array($tab, OperationalReportRecapService::tabs(), true);
    }

    public function exportTab(User $user, string $tab): bool
    {
        return $this->viewTab($user, $tab);
    }

    public function export(User $user, string $type): bool
    {
        return $this->viewType($user, $type);
    }
}
