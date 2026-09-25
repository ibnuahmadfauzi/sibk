<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BkCase;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\ApiTrialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApiTrialSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_trial_seeder_creates_only_required_accounts_and_is_idempotent(): void
    {
        $this->seed(ApiTrialSeeder::class);
        $this->seed(ApiTrialSeeder::class);

        $this->assertSame(8, User::query()->count());
        $this->assertSame(6, User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'guru_bk'))->count());
        $this->assertSame(1, User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'koordinator_bk'))->count());
        $this->assertSame(1, User::query()->whereHas('roles', fn ($query) => $query->where('slug', 'admin_it'))->count());
        $this->assertSame(0, Student::query()->count());
        $this->assertSame(0, BkCase::query()->count());
    }
}
