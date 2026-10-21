<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleRequestLoadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_authenticated_pages_read_assigned_roles_once_per_request(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());
        $roleQueries = 0;
        DB::listen(static function ($query) use (&$roleQueries): void {
            if (str_contains(strtolower($query->sql), 'user_roles')) {
                $roleQueries++;
            }
        });

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->assertSame(1, $roleQueries, 'Dashboard should load roles once.');

        $roleQueries = 0;
        $this->get('/account')->assertOk();
        $this->assertSame(1, $roleQueries, 'Account should refresh roles once on its own request.');
    }

    public function test_role_changes_take_effect_on_the_next_request_even_with_same_authenticated_user_object(): void
    {
        $adminRole = Role::query()->where('slug', 'admin_it')->firstOrFail();
        $guruRole = Role::query()->where('slug', 'guru_bk')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach($adminRole);

        $this->actingAs($user)->get('/admin/users')->assertOk();

        $user->roles()->sync([$guruRole->id]);
        $this->get('/admin/users')->assertForbidden();
        $this->get('/cases')->assertOk();

        $guruRole->update(['is_active' => false]);
        $this->get('/cases')->assertForbidden();
    }

    public function test_guest_and_inactive_user_are_still_rejected(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));

        $user = User::factory()->inactive()->create();
        $user->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
