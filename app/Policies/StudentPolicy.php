<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['guru_bk', 'koordinator_bk']);
    }

    public function view(User $user, Student $student): bool
    {
        return $this->viewAny($user)
            && Student::query()->accessibleTo($user)->whereKey($student->getKey())->exists();
    }

    public function viewSensitive(User $user, Student $student): bool
    {
        return Student::query()->professionallyAccessibleTo($user)->whereKey($student->getKey())->exists();
    }
}
