<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BkCase;
use App\Models\Student;
use App\Models\User;

class CasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['guru_bk', 'koordinator_bk', 'waka_kesiswaan']);
    }

    public function view(User $user, BkCase $case): bool
    {
        if ($user->hasRole('koordinator_bk')) {
            return true;
        }

        if ($user->hasRole('waka_kesiswaan') && $case->coordinations()->where('waka_user_id', $user->getKey())->exists()) {
            return true;
        }

        if (! $user->hasRole('guru_bk')) {
            return false;
        }

        if ($case->hasActiveAssignmentFor($user)) {
            return true;
        }

        return $case->student_id !== null && Student::query()
            ->forActiveTeacherAssignment($user, now())
            ->whereKey($case->student_id)
            ->exists();
    }

    public function viewInternal(User $user, BkCase $case): bool
    {
        return $user->hasRole('guru_bk') && $case->hasActiveAssignmentFor($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('guru_bk');
    }

    public function update(User $user, BkCase $case): bool
    {
        return $case->closed_at === null
            && $user->hasRole('guru_bk')
            && $case->hasActiveAssignmentFor($user);
    }

    public function resolve(User $user, BkCase $case): bool
    {
        return $this->update($user, $case);
    }

    public function assign(User $user, BkCase $case): bool
    {
        return $case->closed_at === null && $user->hasRole('koordinator_bk');
    }

    public function coordinate(User $user, BkCase $case): bool
    {
        return $case->closed_at === null
            && ($user->hasRole('koordinator_bk') || $this->update($user, $case));
    }
}
