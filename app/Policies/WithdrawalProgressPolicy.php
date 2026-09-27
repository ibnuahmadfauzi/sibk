<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\WithdrawalProgress;
use App\Models\User;

final class WithdrawalProgressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasAnyRole(['guru_bk', 'koordinator_bk']);
    }

    public function view(User $user, WithdrawalProgress $withdrawal): bool
    {
        return $this->viewAny($user)
            && WithdrawalProgress::query()->accessibleTo($user)->whereKey($withdrawal->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasRole('guru_bk');
    }

    public function update(User $user, WithdrawalProgress $withdrawal): bool
    {
        return $this->create($user)
            && Student::query()->forActiveTeacherAssignment($user)
                ->whereKey($withdrawal->student_id)->exists();
    }
}
