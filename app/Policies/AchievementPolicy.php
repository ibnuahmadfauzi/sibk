<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Achievement;
use App\Models\Student;
use App\Models\User;

class AchievementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['guru_bk', 'koordinator_bk', 'waka_kesiswaan']);
    }

    public function view(User $user, Achievement $achievement): bool
    {
        return Achievement::query()->accessibleTo($user)->whereKey($achievement->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('guru_bk');
    }

    public function update(User $user, Achievement $achievement): bool
    {
        return $user->hasRole('guru_bk')
            && $achievement->recorded_by === $user->getKey()
            && $achievement->isPending()
            && Student::query()->active()->professionallyAccessibleTo($user)->whereKey($achievement->student_id)->exists();
    }

    public function verify(User $user, Achievement $achievement): bool
    {
        return $user->hasRole('koordinator_bk') && $achievement->isPending();
    }
}
