<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasRole('admin_it');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->hasRole('admin_it') || $actor->is($target);
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole('admin_it');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->hasRole('admin_it');
    }
}
