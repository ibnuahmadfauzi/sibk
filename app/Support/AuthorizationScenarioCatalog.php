<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\CaseCoordination;
use App\Models\Consultation;
use App\Models\FollowUp;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\CarbonImmutable;

final class AuthorizationScenarioCatalog
{
    public const PREFIX = 'RBAC-';

    public const DATASET_VERSION = '2026-08-21.1';

    /** @return array<string, array{name: string, email: string, roles: list<string>, active: bool}> */
    public static function actors(): array
    {
        return [
            'guru_a' => ['name' => 'RBAC Guru BK A', 'email' => 'rbac.guru.a@ruangbk.test', 'roles' => ['guru_bk'], 'active' => true],
            'guru_b' => ['name' => 'RBAC Guru BK B', 'email' => 'rbac.guru.b@ruangbk.test', 'roles' => ['guru_bk'], 'active' => true],
            'koordinator' => ['name' => 'RBAC Koordinator BK', 'email' => 'rbac.koordinator@ruangbk.test', 'roles' => ['koordinator_bk'], 'active' => true],
            'waka_a' => ['name' => 'RBAC Waka Kesiswaan A', 'email' => 'rbac.waka.a@ruangbk.test', 'roles' => ['waka_kesiswaan'], 'active' => true],
            'waka_b' => ['name' => 'RBAC Waka Kesiswaan B', 'email' => 'rbac.waka.b@ruangbk.test', 'roles' => ['waka_kesiswaan'], 'active' => true],
            'admin' => ['name' => 'RBAC Admin IT', 'email' => 'rbac.admin@ruangbk.test', 'roles' => ['admin_it'], 'active' => true],
            'multi_role' => ['name' => 'RBAC Guru dan Koordinator', 'email' => 'rbac.multi@ruangbk.test', 'roles' => ['guru_bk', 'koordinator_bk'], 'active' => true],
            'guru_inactive' => ['name' => 'RBAC Guru BK Nonaktif', 'email' => 'rbac.guru.nonaktif@ruangbk.test', 'roles' => ['guru_bk'], 'active' => false],
        ];
    }

    /** @return array<string, array{label: string, precondition: string}> */
    public static function resourceDefinitions(): array
    {
        return [
            'dashboard' => ['label' => 'Dashboard operasional', 'precondition' => 'Memerlukan sesi akun aktif.'],
            'form_login' => ['label' => 'Form login', 'precondition' => 'Tidak memerlukan sesi.'],
            'students' => ['label' => 'Daftar murid Guru A', 'precondition' => 'Alpha terscope lewat XII-A; Beta lewat K-RBAC-003; Gamma dan Delta di luar scope saat baseline.'],
            'student_a' => ['label' => 'RBAC Murid Alpha / RBAC-STUDENT-A', 'precondition' => 'Berada di RBAC XII-A yang aktif ditugaskan kepada Guru A.'],
            'student_class_b_future_assignment' => ['label' => 'RBAC Murid Delta / RBAC-STUDENT-FUTURE', 'precondition' => 'Berada di RBAC XII-B; penugasan Guru A baru berlaku {guru_a_class_b_start}.'],
            'case_a' => ['label' => 'K-RBAC-001 — kasus Alpha milik Guru A', 'precondition' => 'Guru A memiliki penugasan kasus aktif dan scope kelas aktif.'],
            'case_b' => ['label' => 'K-RBAC-002 — kasus Beta milik Guru B', 'precondition' => 'Tidak ditugaskan kepada Guru A; hanya dikoordinasikan kepada Waka A.'],
            'case_special' => ['label' => 'K-RBAC-003 — kasus Beta dengan penugasan tambahan Guru A', 'precondition' => 'Guru A memiliki penugasan kasus tambahan aktif khusus pada kasus ini.'],
            'case_class_b_future_assignment' => ['label' => 'K-RBAC-005 — kasus Delta pada kelas penugasan masa depan', 'precondition' => 'Guru B masih aktif sampai {guru_b_class_b_end}; Guru A baru mulai {guru_a_class_b_start}.'],
            'report_service_recap' => ['label' => 'Laporan rekap layanan BK Guru A', 'precondition' => 'Dataset mengikuti scope profesional Guru A pada {baseline_date}.'],
            'follow_up_b_under_case_a' => ['label' => 'Tindak lanjut K-RBAC-002 dipasang di URL K-RBAC-001', 'precondition' => 'Tindak lanjut adalah milik K-RBAC-002, bukan K-RBAC-001.'],
            'notification_a' => ['label' => 'RBAC Notifikasi Guru A', 'precondition' => 'Notifikasi dimiliki Guru A dan menargetkan K-RBAC-003.'],
            'notification_b' => ['label' => 'RBAC Notifikasi Guru B', 'precondition' => 'Notifikasi dimiliki Guru B, bukan Guru A.'],
            'consultation_a' => ['label' => 'KNS-RBAC-001 — konsultasi Alpha oleh Guru A', 'precondition' => 'Catatan privat memakai marker RBAC-PRIVATE-A.'],
            'consultation_b' => ['label' => 'KNS-RBAC-002 — konsultasi Beta oleh Guru B', 'precondition' => 'Terhubung ke K-RBAC-002 yang dikoordinasikan kepada Waka A; konsultasi tetap tertutup untuk Waka.'],
            'consultation_history' => ['label' => 'KNS-RBAC-003 — histori Gamma oleh Guru A', 'precondition' => 'Gamma kini terscope akun multi-role; pencatat konsultasi lama tetap Guru A.'],
            'report_consultation' => ['label' => 'Laporan konsultasi', 'precondition' => 'Tipe laporan konsultasi tidak tersedia bagi Waka.'],
            'achievement_verified' => ['label' => 'RBAC-Prestasi-Terverifikasi milik Beta', 'precondition' => 'Status terverifikasi dan murid mempunyai kasus yang dikoordinasikan kepada Waka A.'],
            'achievement_pending' => ['label' => 'RBAC-Prestasi-Menunggu milik Beta', 'precondition' => 'Status masih menunggu verifikasi.'],
            'users' => ['label' => 'Pengelolaan akun', 'precondition' => 'Hanya fungsi Admin IT.'],
            'data_master' => ['label' => 'Data Master', 'precondition' => 'Hanya fungsi teknis Admin IT.'],
            'cases' => ['label' => 'Daftar kasus layanan BK', 'precondition' => 'Admin IT tidak memiliki hak membaca layanan BK.'],
            'reports' => ['label' => 'Katalog laporan layanan BK', 'precondition' => 'Admin IT tidak memiliki hak laporan layanan.'],
            'case_assignments' => ['label' => 'Pengelolaan penugasan kasus', 'precondition' => 'Akun multi-role memiliki fungsi Koordinator.'],
            'student_multi' => ['label' => 'RBAC Murid Gamma / RBAC-STUDENT-MULTI', 'precondition' => 'Pindah dari XII-A ke XII-M; scope Guru A berakhir {guru_a_multi_end}, scope multi-role mulai {baseline_date}.'],
            'class_assignment_manage' => ['label' => 'Pengelolaan penugasan kelas', 'precondition' => 'Fungsi Koordinator; role Guru BK saja tidak cukup.'],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function scenarios(): array
    {
        return [
            self::scenario('RBAC-001', 'AUTH-01', 'guest', 'Akses fungsi operasional', 'dashboard', 'URL langsung', 'GET', 'dashboard.preview', 'Deny', 'Redirect ke login', mustAppear: 'Form login'),
            self::scenario('RBAC-002', 'AUTH-01', 'guru_a', 'Akses fungsi operasional', 'dashboard', 'UI', 'GET', 'dashboard.preview', 'Allow', 'HTTP 200', mustAppear: 'Dashboard'),
            self::scenario('RBAC-003', 'AUTH-01', 'credential_salah', 'Autentikasi', 'form_login', 'UI', 'POST', 'login.store', 'Deny', 'Kembali ke login dengan pesan kredensial salah', mustAppear: 'Email atau kata sandi tidak sesuai'),
            self::scenario('RBAC-004', 'AUTH-01', 'guru_inactive', 'Autentikasi tanpa pengungkapan status akun', 'form_login', 'UI', 'POST', 'login.store', 'Deny', 'Pesan generik tanpa mengungkap status akun', mustAppear: 'Email atau kata sandi tidak sesuai', mustNotAppear: 'Akun nonaktif'),
            self::scenario('RBAC-005', 'AUTH-01', 'guru_a_dinonaktifkan_saat_sesi_aktif', 'Pencabutan sesi akun yang dinonaktifkan', 'dashboard', 'UI dan URL langsung', 'GET', 'dashboard.preview', 'Deny', 'Sesi aktif diakhiri dan redirect ke login', mustAppear: 'Akun tidak aktif. Hubungi Admin IT sekolah.'),

            self::scenario('RBAC-006', 'AUTH-02', 'guru_a', 'Daftar murid terscope', 'students', 'UI', 'GET', 'students.index', 'Allow terbatas', 'Hanya Alpha dan Beta yang tampil', mustAppear: 'RBAC Murid Alpha | RBAC Murid Beta', mustNotAppear: 'RBAC Murid Gamma | RBAC Murid Delta'),
            self::scenario('RBAC-007', 'AUTH-02', 'guru_a', 'Lihat profil murid', 'student_a', 'URL langsung', 'GET', 'students.show', 'Allow', 'HTTP 200', ['student' => 'student_a'], mustAppear: 'RBAC Murid Alpha'),
            self::scenario('RBAC-008', 'AUTH-02', 'guru_a', 'Lihat profil murid di kelas penugasan masa depan', 'student_class_b_future_assignment', 'URL langsung', 'GET', 'students.show', 'Deny 403', 'HTTP 403 karena penugasan Guru A belum berlaku', ['student' => 'student_class_b_future_assignment']),
            self::scenario('RBAC-009', 'AUTH-02', 'guru_a', 'Lihat kasus milik sendiri', 'case_a', 'URL langsung', 'GET', 'cases.show', 'Allow', 'HTTP 200', ['case' => 'case_a'], mustAppear: 'K-RBAC-001'),
            self::scenario('RBAC-010', 'AUTH-02', 'guru_a', 'Lihat kasus Guru B tanpa penugasan kasus', 'case_b', 'URL langsung', 'GET', 'cases.show', 'Deny 403', 'HTTP 403; akses Beta melalui K-RBAC-003 tidak membuka K-RBAC-002', ['case' => 'case_b']),
            self::scenario('RBAC-011', 'AUTH-02', 'guru_a', 'Lihat kasus penugasan khusus', 'case_special', 'URL langsung', 'GET', 'cases.show', 'Allow', 'HTTP 200', ['case' => 'case_special'], mustAppear: 'K-RBAC-003'),
            self::scenario('RBAC-012', 'AUTH-02', 'guru_a', 'Lihat kasus pada kelas dengan penugasan Guru A yang belum berlaku', 'case_class_b_future_assignment', 'URL langsung', 'GET', 'cases.show', 'Deny 403', 'HTTP 403 karena periode penugasan Guru A belum dimulai', ['case' => 'case_class_b_future_assignment']),
            self::scenario('RBAC-013', 'AUTH-02', 'guru_a', 'Pratinjau laporan terscope', 'report_service_recap', 'URL langsung', 'GET', 'reports.preview', 'Allow terbatas', 'HTTP 200 dan hanya data terscope', [], ['type' => 'rekap-layanan-bk'], 'K-RBAC-001 | K-RBAC-003 | K-RBAC-004 | KNS-RBAC-001 | KNS-RBAC-002', 'K-RBAC-002 | K-RBAC-005 | KNS-RBAC-003 | RBAC-PRIVATE | RBAC-INTERNAL'),

            self::scenario('RBAC-014', 'AUTH-03', 'guru_a', 'Nested resource binding', 'follow_up_b_under_case_a', 'URL langsung', 'GET', 'cases.follow-ups.edit', 'Deny 404', 'HTTP 404 tanpa isi tindak lanjut', ['case' => 'case_a', 'followUp' => 'follow_up_b']),
            self::scenario('RBAC-015', 'AUTH-03', 'guru_a', 'Buka notifikasi milik sendiri', 'notification_a', 'URL langsung', 'GET', 'notifications.open', 'Allow + redirect', 'Redirect ke K-RBAC-003', ['notification' => 'notification_a'], mustAppear: 'K-RBAC-003'),
            self::scenario('RBAC-016', 'AUTH-03', 'guru_a', 'Buka notifikasi pengguna lain', 'notification_b', 'URL langsung', 'GET', 'notifications.open', 'Deny 403', 'HTTP 403', ['notification' => 'notification_b']),
            self::scenario('RBAC-017', 'AUTH-03', 'waka_a', 'Mutasi kasus read-only', 'case_b', 'Automated companion', 'POST', 'cases.coordinations.store', 'Deny 403', 'HTTP 403 dan database tidak berubah', ['case' => 'case_b']),

            self::scenario('RBAC-018', 'AUTH-04', 'guru_a', 'Lihat catatan konsultasi privat', 'consultation_a', 'URL langsung', 'GET', 'consultations.show', 'Allow penuh', 'HTTP 200 dan marker privat tampil', ['consultation' => 'consultation_a'], mustAppear: 'KNS-RBAC-001 | RBAC-PRIVATE-A'),
            self::scenario('RBAC-019', 'AUTH-04', 'koordinator', 'Lihat metadata konsultasi', 'consultation_a', 'URL langsung', 'GET', 'consultations.show', 'Allow terbatas/redacted', 'HTTP 200 tanpa marker privat', ['consultation' => 'consultation_a'], mustAppear: 'KNS-RBAC-001 | RBAC ringkasan umum Alpha', mustNotAppear: 'RBAC-PRIVATE-A'),
            self::scenario('RBAC-020', 'AUTH-04', 'waka_a', 'Lihat konsultasi kasus terkoordinasi', 'consultation_b', 'URL langsung', 'GET', 'consultations.show', 'Deny 403', 'HTTP 403; koordinasi kasus tidak membuka konsultasi', ['consultation' => 'consultation_b']),
            self::scenario('RBAC-021', 'AUTH-04', 'admin', 'Lihat konsultasi', 'consultation_a', 'URL langsung', 'GET', 'consultations.show', 'Deny 403', 'HTTP 403', ['consultation' => 'consultation_a']),
            self::scenario('RBAC-022', 'AUTH-04', 'multi_role', 'Baca histori privat murid dalam scope baru', 'consultation_history', 'URL langsung', 'GET', 'consultations.show', 'Allow penuh/read-only', 'HTTP 200 dan marker privat histori tampil', ['consultation' => 'consultation_history'], mustAppear: 'KNS-RBAC-003 | RBAC-PRIVATE-HISTORY'),
            self::scenario('RBAC-023', 'AUTH-04', 'multi_role', 'Ubah catatan Guru BK sebelumnya', 'consultation_history', 'URL langsung', 'GET', 'consultations.edit', 'Deny 403', 'HTTP 403 karena bukan pencatat', ['consultation' => 'consultation_history']),
            self::scenario('RBAC-024', 'AUTH-07', 'multi_role', 'Lihat metadata di luar scope Guru BK', 'consultation_a', 'URL langsung', 'GET', 'consultations.show', 'Allow terbatas/redacted', 'HTTP 200 melalui fungsi Koordinator tanpa marker privat', ['consultation' => 'consultation_a'], mustAppear: 'KNS-RBAC-001 | RBAC ringkasan umum Alpha', mustNotAppear: 'RBAC-PRIVATE-A'),

            self::scenario('RBAC-025', 'AUTH-05', 'waka_a', 'Lihat kasus terkoordinasi', 'case_b', 'URL langsung', 'GET', 'cases.show', 'Allow terbatas/read-only', 'HTTP 200 tanpa catatan internal', ['case' => 'case_b'], mustAppear: 'K-RBAC-002', mustNotAppear: 'RBAC-INTERNAL-CASE-B'),
            self::scenario('RBAC-026', 'AUTH-05', 'waka_a', 'Lihat kasus tanpa koordinasi', 'case_a', 'URL langsung', 'GET', 'cases.show', 'Deny 403', 'HTTP 403', ['case' => 'case_a']),
            self::scenario('RBAC-027', 'AUTH-05', 'waka_b', 'Lihat koordinasi Waka lain', 'case_b', 'URL langsung', 'GET', 'cases.show', 'Deny 403', 'HTTP 403 karena koordinasi ditujukan kepada Waka A', ['case' => 'case_b']),
            self::scenario('RBAC-028', 'AUTH-05', 'waka_a', 'Ubah kasus terkoordinasi', 'case_b', 'URL langsung', 'GET', 'cases.resolve.form', 'Deny 403', 'HTTP 403', ['case' => 'case_b']),
            self::scenario('RBAC-029', 'AUTH-05', 'waka_a', 'Laporan konsultasi', 'report_consultation', 'URL langsung', 'GET', 'reports.preview', 'Deny 403', 'HTTP 403', [], ['type' => 'konsultasi']),
            self::scenario('RBAC-030', 'AUTH-05', 'waka_a', 'Lihat prestasi terverifikasi murid terkoordinasi', 'achievement_verified', 'URL langsung', 'GET', 'achievements.show', 'Allow terbatas/read-only', 'HTTP 200', ['achievement' => 'achievement_verified'], mustAppear: 'RBAC-Prestasi-Terverifikasi'),
            self::scenario('RBAC-031', 'AUTH-05', 'waka_a', 'Lihat prestasi menunggu', 'achievement_pending', 'URL langsung', 'GET', 'achievements.show', 'Deny 403', 'HTTP 403', ['achievement' => 'achievement_pending']),

            self::scenario('RBAC-032', 'AUTH-06', 'admin', 'Kelola akun', 'users', 'UI', 'GET', 'admin.users.index', 'Allow', 'HTTP 200 dan GUI pengelolaan akun', mustAppear: 'Kelola Akun'),
            self::scenario('RBAC-033', 'AUTH-06', 'admin', 'Kelola Data Master', 'data_master', 'UI', 'GET', 'data-master.index', 'Allow', 'HTTP 200', mustAppear: 'Data Master'),
            self::scenario('RBAC-034', 'AUTH-06', 'admin', 'Lihat kasus layanan', 'cases', 'URL langsung', 'GET', 'cases.index', 'Deny 403', 'HTTP 403'),
            self::scenario('RBAC-035', 'AUTH-06', 'admin', 'Lihat profil murid layanan', 'student_a', 'URL langsung', 'GET', 'students.show', 'Deny 403', 'HTTP 403', ['student' => 'student_a']),
            self::scenario('RBAC-036', 'AUTH-06', 'admin', 'Lihat laporan layanan', 'reports', 'URL langsung', 'GET', 'reports.index', 'Deny 403', 'HTTP 403'),
            self::scenario('RBAC-037', 'AUTH-06', 'admin', 'Lihat prestasi', 'achievement_verified', 'URL langsung', 'GET', 'achievements.show', 'Deny 403', 'HTTP 403', ['achievement' => 'achievement_verified']),

            self::scenario('RBAC-038', 'AUTH-07', 'multi_role', 'Kelola penugasan kasus', 'case_assignments', 'UI', 'GET', 'assignments.cases.index', 'Allow sebagai Koordinator', 'HTTP 200', mustAppear: 'Penugasan Kasus'),
            self::scenario('RBAC-039', 'AUTH-07', 'multi_role', 'Lihat murid dalam scope Guru BK', 'student_multi', 'URL langsung', 'GET', 'students.show', 'Allow sebagai Guru BK', 'HTTP 200', ['student' => 'student_multi'], mustAppear: 'RBAC Murid Gamma'),
            self::scenario('RBAC-040', 'AUTH-07', 'guru_a', 'Kelola penugasan kelas', 'class_assignment_manage', 'URL langsung', 'GET', 'assignments.classes.manage', 'Deny 403', 'HTTP 403'),
            self::scenario('RBAC-041', 'AUTH-07', 'koordinator', 'Kelola penugasan kelas', 'class_assignment_manage', 'UI', 'GET', 'assignments.classes.manage', 'Allow', 'HTTP 200', mustAppear: 'Kelola Penugasan'),
        ];
    }

    /** @return array<string, int> */
    public static function resourceIds(): array
    {
        $users = User::query()->whereIn('email', array_column(self::actors(), 'email'))->pluck('id', 'email');

        return array_filter([
            ...collect(self::actors())->mapWithKeys(fn (array $actor, string $key): array => [$key => $users->get($actor['email'])])->all(),
            'student_a' => Student::query()->where('dapodik_id', 'RBAC-STUDENT-A')->value('id'),
            'student_b' => Student::query()->where('dapodik_id', 'RBAC-STUDENT-B')->value('id'),
            'student_multi' => Student::query()->where('dapodik_id', 'RBAC-STUDENT-MULTI')->value('id'),
            'student_class_b_future_assignment' => Student::query()->where('dapodik_id', 'RBAC-STUDENT-FUTURE')->value('id'),
            'case_a' => BkCase::query()->where('registration_number', 'K-RBAC-001')->value('id'),
            'case_b' => BkCase::query()->where('registration_number', 'K-RBAC-002')->value('id'),
            'case_special' => BkCase::query()->where('registration_number', 'K-RBAC-003')->value('id'),
            'case_closed' => BkCase::query()->where('registration_number', 'K-RBAC-004')->value('id'),
            'case_class_b_future_assignment' => BkCase::query()->where('registration_number', 'K-RBAC-005')->value('id'),
            'consultation_a' => Consultation::query()->where('registration_number', 'KNS-RBAC-001')->value('id'),
            'consultation_b' => Consultation::query()->where('registration_number', 'KNS-RBAC-002')->value('id'),
            'consultation_history' => Consultation::query()->where('registration_number', 'KNS-RBAC-003')->value('id'),
            'follow_up_a' => FollowUp::query()->whereHas('case', fn ($query) => $query->where('registration_number', 'K-RBAC-001'))->value('id'),
            'follow_up_b' => FollowUp::query()->whereHas('case', fn ($query) => $query->where('registration_number', 'K-RBAC-002'))->value('id'),
            'coordination_b' => CaseCoordination::query()->whereHas('case', fn ($query) => $query->where('registration_number', 'K-RBAC-002'))->value('id'),
            'achievement_verified' => Achievement::query()->where('activity_name', 'RBAC-Prestasi-Terverifikasi')->value('id'),
            'achievement_pending' => Achievement::query()->where('activity_name', 'RBAC-Prestasi-Menunggu')->value('id'),
            'notification_a' => UserNotification::query()->where('deduplication_key', 'RBAC-NOTIFICATION-A')->value('id'),
            'notification_b' => UserNotification::query()->where('deduplication_key', 'RBAC-NOTIFICATION-B')->value('id'),
        ], static fn (mixed $value): bool => $value !== null);
    }

    public static function baselineDate(): ?CarbonImmutable
    {
        $date = TeacherAssignment::query()->where('decision_number', 'RBAC-SK-AKTIF-MULTI')->value('effective_from');

        return $date === null ? null : CarbonImmutable::parse($date)->startOfDay();
    }

    /** @return list<array<string, string>> */
    public static function templateRows(): array
    {
        return array_map(function (array $scenario): array {
            $resource = self::resourceDefinitions()[$scenario['resource']];

            return [
                'Scenario ID' => $scenario['id'],
                'Requirement ID' => $scenario['requirement_id'],
                'Actor' => $scenario['actor'],
                'Capability' => $scenario['capability'],
                'Resource Key' => $scenario['resource'],
                'Resource Label' => $resource['label'],
                'Preconditions' => $resource['precondition'],
                'Channel' => $scenario['channel'],
                'Request/Route' => $scenario['method'].' '.$scenario['route'],
                'Expected Access' => $scenario['expected_access'],
                'Expected Result' => $scenario['expected_result'],
                'Must Appear' => $scenario['must_appear'] ?? '',
                'Must Not Appear' => $scenario['must_not_appear'] ?? '',
                'Actual Access' => '',
                'Pass/Fail' => '',
                'Evidence' => '',
                'Notes' => '',
            ];
        }, self::scenarios());
    }

    /** @return list<array<string, string>> */
    public static function resolvedRows(?CarbonImmutable $generatedAt = null): array
    {
        $generatedAt ??= CarbonImmutable::now();
        $baseline = self::baselineDate();
        $resources = self::resourceIds();
        $context = self::dateContext($baseline);

        return array_map(function (array $scenario) use ($resources, $generatedAt, $baseline, $context): array {
            $parameters = [];
            foreach ($scenario['parameters'] ?? [] as $parameter => $resourceKey) {
                $parameters[$parameter] = $resources[$resourceKey] ?? '{'.$resourceKey.'}';
            }
            $parameters = [...$parameters, ...($scenario['query'] ?? [])];
            $resource = self::resourceDefinitions()[$scenario['resource']];

            return [
                'Dataset Version' => self::DATASET_VERSION,
                'Baseline Date' => $baseline?->toDateString() ?? '',
                'Generated At' => $generatedAt->toIso8601String(),
                'Scenario ID' => $scenario['id'],
                'Requirement ID' => $scenario['requirement_id'],
                'Actor' => $scenario['actor'],
                'Capability' => $scenario['capability'],
                'Resource Key' => $scenario['resource'],
                'Resource Label' => self::replaceDateTokens($resource['label'], $context),
                'Preconditions' => self::replaceDateTokens($resource['precondition'], $context),
                'Channel' => $scenario['channel'],
                'Request/Route' => $scenario['method'].' '.route($scenario['route'], $parameters, false),
                'Expected Access' => $scenario['expected_access'],
                'Expected Result' => $scenario['expected_result'],
                'Must Appear' => $scenario['must_appear'] ?? '',
                'Must Not Appear' => $scenario['must_not_appear'] ?? '',
                'Actual Access' => '',
                'Pass/Fail' => '',
                'Evidence' => '',
                'Notes' => '',
            ];
        }, self::scenarios());
    }

    /** @return array<string, string> */
    private static function dateContext(?CarbonImmutable $baseline): array
    {
        if ($baseline === null) {
            return [];
        }

        return [
            '{baseline_date}' => $baseline->toDateString(),
            '{guru_a_class_b_start}' => $baseline->addDays(90)->toDateString(),
            '{guru_b_class_b_end}' => $baseline->addDays(89)->toDateString(),
            '{guru_a_multi_end}' => $baseline->subDay()->toDateString(),
        ];
    }

    /** @param array<string, string> $context */
    private static function replaceDateTokens(string $value, array $context): string
    {
        return strtr($value, $context);
    }

    /** @return array<string, mixed> */
    private static function scenario(
        string $id,
        string $requirementId,
        string $actor,
        string $capability,
        string $resource,
        string $channel,
        string $method,
        string $route,
        string $expectedAccess,
        string $expectedResult,
        array $parameters = [],
        array $query = [],
        string $mustAppear = '',
        string $mustNotAppear = '',
    ): array {
        return array_filter([
            'id' => $id,
            'requirement_id' => $requirementId,
            'actor' => $actor,
            'capability' => $capability,
            'resource' => $resource,
            'channel' => $channel,
            'method' => $method,
            'route' => $route,
            'parameters' => $parameters,
            'query' => $query,
            'expected_access' => $expectedAccess,
            'expected_result' => $expectedResult,
            'must_appear' => $mustAppear,
            'must_not_appear' => $mustNotAppear,
        ], static fn (mixed $value): bool => $value !== [] && $value !== '');
    }
}
