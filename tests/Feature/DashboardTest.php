<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_quick_actions_provide_an_allowed_icon_and_tone(): void
    {
        $this->seed(RoleSeeder::class);
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('slug', 'guru_bk')->value('id'));
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);

        $actions = app(DashboardService::class)->forUser($teacher, $year)['quick_actions'];

        $this->assertSame(['case', 'consultation', 'report'], array_column($actions, 'icon'));
        $this->assertSame(['primary', 'success', 'info'], array_column($actions, 'tone'));
    }
}
