<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\StudentDeparture;
use App\Models\User;

final class StudentDeparturePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasRole('waka_kesiswaan');
    }

    public function create(User $user, Student $student): bool
    {
        return $user->is_active
            && $user->hasRole('guru_bk')
            && Student::query()->professionallyAccessibleTo($user)->whereKey($student->getKey())->exists();
    }

    public function update(User $user, StudentDeparture $departure): bool
    {
        return $departure->status === StudentDeparture::STATUS_IN_PROGRESS
            && $this->create($user, $departure->student);
    }

    public function finalize(User $user, StudentDeparture $departure): bool
    {
        return $user->is_active && $user->hasRole('koordinator_bk');
    }
}
