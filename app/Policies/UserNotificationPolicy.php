<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;

class UserNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, UserNotification $notification): bool
    {
        return $notification->user_id === $user->getKey();
    }

    public function update(User $user, UserNotification $notification): bool
    {
        return $this->view($user, $notification);
    }
}
