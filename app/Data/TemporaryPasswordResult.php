<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class TemporaryPasswordResult
{
    public function __construct(
        public User $user,
        public string $plainTextPassword,
        public CarbonImmutable $expiresAt,
    ) {}
}
