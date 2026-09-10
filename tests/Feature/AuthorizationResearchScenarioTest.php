<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseCoordination;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\User;
use App\Support\AuthorizationScenarioCatalog;
use App\Support\AuthorizationScenarioVerifier;
use Database\Seeders\AuthorizationScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class AuthorizationResearchScenarioTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, int> */
    private array $resources;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('sibk.seed_accounts.password', 'RBAC-Test-Password-2026');
        $this->seed(AuthorizationScenarioSeeder::class);
        $this->resources = AuthorizationScenarioCatalog::resourceIds();
    }

    public function test_catalog_and_dataset_are_complete_and_idempotent(): void
    {
        $this->assertCount(41, AuthorizationScenarioCatalog::scenarios());
        $this->assertCount(8, AuthorizationScenarioCatalog::actors());
        $this->assertCount(8, User::query()->where('email', 'like', 'rbac.%@ruangbk.test')->get());
        $this->assertSame(2, $this->actor('multi_role')->roles()->count());

        $before = [
            'users' => User::query()->where('email', 'like', 'rbac.%@ruangbk.test')->count(),
            'cases' => BkCase::query()->where('registration_number', 'like', 'K-RBAC-%')->count(),
            'consultations' => Consultation::query()->where('registration_number', 'like', 'KNS-RBAC-%')->count(),
        ];
        $this->seed(AuthorizationScenarioSeeder::class);

        $this->assertSame($before['users'], User::query()->where('email', 'like', 'rbac.%@ruangbk.test')->count());
        $this->assertSame($before['cases'], BkCase::query()->where('registration_number', 'like', 'K-RBAC-%')->count());
        $this->assertSame($before['consultations'], Consultation::query()->where('registration_number', 'like', 'KNS-RBAC-%')->count());
        $this->assertCount(41, AuthorizationScenarioCatalog::resolvedRows());

        $this->assertSame(
            AuthorizationScenarioCatalog::templateRows(),
            $this->readCsv(base_path('docs/testing/rbac-scenario-matrix.csv')),
            'Seluruh isi template CSV harus tetap selaras dengan katalog skenario.',
        );

        $manual = file_get_contents(base_path('docs/testing/rbac-manual-testing.md'));
        $this->assertIsString($manual);
        preg_match_all('/^#### (RBAC-\d{3}) —/m', $manual, $matches);
        $this->assertSame(array_column(AuthorizationScenarioCatalog::scenarios(), 'id'), $matches[1]);
    }

    public function test_baseline_relationships_are_verified_deterministically(): void
    {
        $this->assertSame([], app(AuthorizationScenarioVerifier::class)->verify());
        $this->assertSame('2026-08-21.1', AuthorizationScenarioCatalog::DATASET_VERSION);
        $this->assertSame(now()->toDateString(), AuthorizationScenarioCatalog::baselineDate()?->toDateString());

        $this->assertDatabaseHas('teacher_assignments', [
            'decision_number' => 'RBAC-SK-MASA-DEPAN',
            'effective_from' => now()->addDays(90)->startOfDay()->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('student_class_memberships', [
            'dapodik_id' => 'RBAC-MEMBERSHIP-MOVED-OLD',
            'effective_until' => now()->subDay()->startOfDay()->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('student_class_memberships', [
            'dapodik_id' => 'RBAC-MEMBERSHIP-MOVED-NEW',
            'effective_from' => now()->startOfDay()->toDateTimeString(),
        ]);
    }

    public function test_auth_01_guest_active_and_inactive_enforcement(): void
    {
        $this->get(route('dashboard.preview'))->assertRedirect(route('login'));
        $guru = $this->actor('guru_a');
        $this->actingAs($guru)->get(route('dashboard.preview'))->assertOk();

        $guru->update(['is_active' => false, 'deactivated_at' => now()]);
        $this->get(route('dashboard.preview'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Akun tidak aktif. Hubungi Admin IT sekolah.']);

        auth()->logout();
        $this->post(route('login.store'), [
            'email' => AuthorizationScenarioCatalog::actors()['guru_inactive']['email'],
            'password' => 'RBAC-Test-Password-2026',
        ])->assertSessionHasErrors(['credentials' => 'Email atau kata sandi tidak sesuai.']);
    }

    public function test_auth_02_guru_scope_and_special_assignment_apply_to_direct_urls(): void
    {
        $guru = $this->actor('guru_a');

        $this->actingAs($guru)->get(route('students.show', $this->resources['student_a']))->assertOk();
        $this->actingAs($guru)->get(route('students.show', $this->resources['student_class_b_future_assignment']))->assertForbidden();
        $this->actingAs($guru)->get(route('cases.show', $this->resources['case_a']))->assertOk();
        $this->actingAs($guru)->get(route('cases.show', $this->resources['case_b']))->assertForbidden();
        $this->actingAs($guru)->get(route('cases.show', $this->resources['case_special']))->assertOk();
        $this->actingAs($guru)->get(route('cases.show', $this->resources['case_class_b_future_assignment']))->assertForbidden();

        $this->actingAs($guru)->get(route('students.index'))
            ->assertOk()
            ->assertSee('RBAC Murid Alpha')
            ->assertSee('RBAC Murid Beta')
            ->assertDontSee('RBAC Murid Gamma')
            ->assertDontSee('RBAC Murid Delta');
    }

    public function test_future_class_assignment_stays_inactive_during_manual_testing_window(): void
    {
        $guru = $this->actor('guru_a');
        $baseline = now();

        try {
            Date::setTestNow($baseline->copy()->addDays(30));
            $this->actingAs($guru)->get(route('cases.show', $this->resources['case_class_b_future_assignment']))->assertForbidden();

            Date::setTestNow($baseline->copy()->addDays(91));
            $this->actingAs($guru)->get(route('cases.show', $this->resources['case_class_b_future_assignment']))->assertOk();
        } finally {
            Date::setTestNow();
        }
    }

    public function test_auth_03_scoped_binding_notification_ownership_and_denied_mutation(): void
    {
        $guru = $this->actor('guru_a');
        $this->actingAs($guru)->get(route('cases.follow-ups.edit', [
            'case' => $this->resources['case_a'],
            'followUp' => $this->resources['follow_up_b'],
        ]))->assertNotFound();

        $this->actingAs($guru)->get(route('notifications.open', $this->resources['notification_a']))
            ->assertRedirect(route('cases.show', $this->resources['case_special']));
        $this->actingAs($guru)->get(route('notifications.open', $this->resources['notification_b']))->assertForbidden();

        $before = CaseCoordination::query()->count();
        $this->actingAs($this->actor('waka_a'))->post(route('cases.coordinations.store', $this->resources['case_b']), [
            'waka_user_id' => $this->resources['waka_b'],
            'coordination_need' => 'Mutasi harus ditolak.',
        ])->assertForbidden();
        $this->assertSame($before, CaseCoordination::query()->count());
    }

    public function test_auth_04_sensitive_consultation_is_redacted_per_professional_scope(): void
    {
        $this->actingAs($this->actor('guru_a'))
            ->get(route('consultations.show', $this->resources['consultation_a']))
            ->assertOk()->assertSee('RBAC-PRIVATE-A');

        $this->actingAs($this->actor('koordinator'))
            ->get(route('consultations.show', $this->resources['consultation_a']))
            ->assertOk()->assertDontSee('RBAC-PRIVATE-A');

        $this->actingAs($this->actor('multi_role'))
            ->get(route('consultations.show', $this->resources['consultation_history']))
            ->assertOk()->assertSee('RBAC-PRIVATE-HISTORY');
        $this->actingAs($this->actor('multi_role'))
            ->get(route('consultations.edit', $this->resources['consultation_history']))
            ->assertForbidden();
        $this->actingAs($this->actor('multi_role'))
            ->get(route('consultations.show', $this->resources['consultation_a']))
            ->assertOk()->assertDontSee('RBAC-PRIVATE-A');

        $this->actingAs($this->actor('waka_a'))
            ->get(route('consultations.show', $this->resources['consultation_b']))->assertForbidden();
        $this->actingAs($this->actor('admin'))
            ->get(route('consultations.show', $this->resources['consultation_a']))->assertForbidden();
    }

    public function test_auth_05_waka_is_limited_to_coordinated_read_only_data(): void
    {
        $waka = $this->actor('waka_a');
        $this->actingAs($waka)->get(route('cases.show', $this->resources['case_b']))
            ->assertOk()->assertDontSee('RBAC-INTERNAL-CASE-B');
        $this->actingAs($waka)->get(route('cases.show', $this->resources['case_a']))->assertForbidden();
        $this->actingAs($this->actor('waka_b'))->get(route('cases.show', $this->resources['case_b']))->assertForbidden();
        $this->actingAs($waka)->get(route('cases.resolve.form', $this->resources['case_b']))->assertForbidden();
        $this->actingAs($waka)->get(route('achievements.show', $this->resources['achievement_verified']))->assertOk();
        $this->actingAs($waka)->get(route('achievements.show', $this->resources['achievement_pending']))->assertForbidden();
        $this->actingAs($waka)->get(route('reports.preview', ['type' => 'konsultasi']))->assertForbidden();
    }

    public function test_auth_06_admin_technical_role_does_not_open_bk_services(): void
    {
        $admin = $this->actor('admin');
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('data-master.index'))->assertOk();

        foreach ([
            route('cases.index'),
            route('students.show', $this->resources['student_a']),
            route('reports.index'),
            route('achievements.show', $this->resources['achievement_verified']),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertForbidden();
        }
    }

    public function test_auth_07_multi_role_functions_are_evaluated_separately(): void
    {
        $multi = $this->actor('multi_role');
        $this->actingAs($multi)->get(route('assignments.cases.index'))->assertOk();
        $this->actingAs($multi)->get(route('assignments.classes.manage'))->assertOk();
        $this->actingAs($multi)->get(route('students.show', $this->resources['student_multi']))->assertOk();
        $this->actingAs($this->actor('guru_a'))->get(route('assignments.classes.manage'))->assertForbidden();
    }

    public function test_report_preview_and_csv_share_scope_and_exclude_private_markers(): void
    {
        $query = [
            'type' => 'rekap-layanan-bk',
            'date_start' => now()->subDays(90)->toDateString(),
            'date_end' => now()->toDateString(),
        ];
        $guru = $this->actor('guru_a');
        $preview = $this->actingAs($guru)->get(route('reports.preview', $query))->assertOk();
        $csv = $this->actingAs($guru)->get(route('reports.export', [...$query, 'format' => 'csv']))->assertOk();

        $content = $csv->streamedContent();
        foreach (['K-RBAC-001', 'K-RBAC-003', 'K-RBAC-004', 'KNS-RBAC-001', 'KNS-RBAC-002'] as $allowed) {
            $preview->assertSee($allowed);
            $this->assertStringContainsString($allowed, $content);
        }
        foreach (['K-RBAC-002', 'K-RBAC-005', 'KNS-RBAC-003', 'RBAC-PRIVATE-A', 'RBAC-INTERNAL-CASE-A'] as $forbidden) {
            $preview->assertDontSee($forbidden);
            $this->assertStringNotContainsString($forbidden, $content);
        }
    }

    public function test_allowed_case_creation_writes_sanitized_audit_and_reset_removes_created_record(): void
    {
        $guru = $this->actor('guru_a');
        $response = $this->actingAs($guru)->post(route('cases.store'), [
            'student_id' => $this->resources['student_a'],
            'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
            'service_field_id' => $this->reference('service_field', 'pribadi')->id,
            'service_date' => now()->toDateString(),
            'initial_info' => 'RBAC informasi untuk pengujian mutasi berhasil.',
            'initial_action' => 'RBAC tindakan awal untuk pengujian.',
            'internal_note' => 'RBAC-PRIVATE-MUTATION',
        ]);
        $response->assertRedirect();

        $created = BkCase::query()->where('created_by', $guru->id)->latest('id')->firstOrFail();
        $audit = AuditLog::query()->where('action', 'case.created')->where('auditable_id', $created->id)->latest('id')->firstOrFail();
        $this->assertStringNotContainsString('RBAC-PRIVATE-MUTATION', json_encode($audit->toArray(), JSON_THROW_ON_ERROR));

        $this->seed(AuthorizationScenarioSeeder::class);
        $this->assertDatabaseMissing('cases', ['id' => $created->id]);
        $this->assertDatabaseMissing('audit_logs', ['id' => $audit->id]);
    }

    public function test_research_command_generates_resolved_csv_without_password(): void
    {
        $before = glob(storage_path('app/testing/rbac-results-*.csv')) ?: [];
        $this->artisan('rbac:scenario-reset', ['--force' => true])->assertSuccessful();
        $after = glob(storage_path('app/testing/rbac-results-*.csv')) ?: [];
        $this->assertNotEmpty($after);
        $latest = collect($after)->sort()->last();
        $this->assertIsString($latest);
        $contents = file_get_contents($latest);
        $currentResources = AuthorizationScenarioCatalog::resourceIds();
        $this->assertIsString($contents);
        $this->assertStringContainsString('RBAC-001', $contents);
        $this->assertStringContainsString('/students/'.$currentResources['student_a'], $contents);
        $this->assertStringContainsString(AuthorizationScenarioCatalog::DATASET_VERSION, $contents);
        $this->assertStringContainsString('Resource Label', $contents);
        $this->assertStringContainsString('Preconditions', $contents);
        $this->assertStringNotContainsString('RBAC-Test-Password-2026', $contents);
        $this->assertSame([], app(AuthorizationScenarioVerifier::class)->verify($latest));

        $this->artisan('rbac:scenario-verify', ['--csv' => $latest])->assertSuccessful();

        foreach (array_diff($after, $before) as $generated) {
            @unlink($generated);
        }
    }

    public function test_research_command_is_refused_in_production_without_mutation(): void
    {
        $before = BkCase::query()->where('registration_number', 'like', 'K-RBAC-%')->count();
        $originalEnvironment = app()->environment();
        app()->detectEnvironment(static fn (): string => 'production');
        try {
            $this->artisan('rbac:scenario-reset', ['--force' => true])->assertFailed();
        } finally {
            app()->detectEnvironment(static fn (): string => $originalEnvironment);
        }

        $this->assertSame($before, BkCase::query()->where('registration_number', 'like', 'K-RBAC-%')->count());
    }

    private function actor(string $key): User
    {
        $definition = AuthorizationScenarioCatalog::actors()[$key];

        return User::query()->where('email', $definition['email'])->firstOrFail();
    }

    private function reference(string $category, string $code): ReferenceValue
    {
        return ReferenceValue::query()->where('category', $category)->where('code', $code)->firstOrFail();
    }

    /** @return list<array<string, string>> */
    private function readCsv(string $path): array
    {
        $stream = fopen($path, 'rb');
        $this->assertNotFalse($stream);
        $header = fgetcsv($stream, escape: '');
        $this->assertIsArray($header);
        $header[0] = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0], '"');
        $rows = [];
        while (($values = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = array_combine($header, $values);
        }
        fclose($stream);

        return $rows;
    }
}
