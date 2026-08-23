<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;

class ConsultationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['guru_bk', 'koordinator_bk']);
    }

    public function view(User $user, Consultation $consultation): bool
    {
        return $user->hasRole('koordinator_bk') || $consultation->isProfessionallyAccessibleTo($user);
    }

    public function viewSensitive(User $user, Consultation $consultation): bool
    {
        return $consultation->isProfessionallyAccessibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('guru_bk');
    }

    public function update(User $user, Consultation $consultation): bool
    {
        return $consultation->counselor_id === $user->getKey()
            && $consultation->isProfessionallyAccessibleTo($user);
    }
}
