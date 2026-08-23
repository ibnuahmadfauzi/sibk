<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TeacherAssignment;
use App\Models\User;

class TeacherAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('koordinator_bk');
    }

    public function view(User $user, TeacherAssignment $assignment): bool
    {
        return $user->hasRole('koordinator_bk');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('koordinator_bk');
    }
}
