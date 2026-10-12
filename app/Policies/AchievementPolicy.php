<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Achievement;
use App\Models\User;

class AchievementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['guru_bk', 'koordinator_bk', 'waka_kesiswaan']);
    }

    public function view(User $user, Achievement $achievement): bool
    {
        return $this->viewAny($user)
            && Achievement::query()->accessibleTo($user)->whereKey($achievement->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('waka_kesiswaan');
    }

    public function update(User $user, Achievement $achievement): bool
    {
        return $user->hasRole('waka_kesiswaan') && $this->view($user, $achievement);
    }

    public function delete(User $user, Achievement $achievement): bool
    {
        return $this->update($user, $achievement);
    }
}
