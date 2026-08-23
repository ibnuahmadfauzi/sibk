<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TeacherAssignment;
use App\Models\User;

class TeacherAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['guru_bk', 'koordinator_bk', 'waka_kesiswaan', 'admin_it']);
    }

    public function view(User $user, TeacherAssignment $assignment): bool
    {
        return $user->hasAnyRole(['koordinator_bk', 'waka_kesiswaan', 'admin_it'])
            || ($user->hasRole('guru_bk') && $assignment->user_id === $user->getKey());
    }

    public function create(User $user): bool
    {
        return $user->hasRole('koordinator_bk');
    }
}
