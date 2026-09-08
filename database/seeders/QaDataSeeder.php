<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\AuditLog;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\CaseCoordination;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ConsultationPrivateNote;
use App\Models\ExternalTatibRecord;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QaDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new InvalidArgumentException('Dataset dummy QA hanya boleh dijalankan pada environment local atau testing.');
        }

        $password = (string) (config('sibk.seed_accounts.password') ?: '12345678');
        if (mb_strlen($password) < 8) {
            throw new InvalidArgumentException('Password minimal terdiri dari 8 karakter.');
        }

        // Pastikan tabel master reference & roles telah tersedia
        $this->call([RoleSeeder::class, ReferenceSeeder::class]);

        DB::transaction(function () use ($password): void {
            // Bersihkan data QA lama secara aman (tidak menyentuh data developer)
            QaDataResetter::reset();

            $this->seedQaData($password);
        });
    }

    private function seedQaData(string $password): void
    {
        $today = CarbonImmutable::today();
        $refs = $this->loadReferences();

        // 1. Akun Pengguna QA
        $users = $this->seedUsers($password);

        // 2. Tahun Ajaran
        $academicYears = $this->seedAcademicYears($today);

        // 3. Rombel / Kelas
        $classrooms = $this->seedClassrooms($academicYears);

        // 4. Penugasan Guru BK ke Rombel (Teacher Assignments)
        $this->seedTeacherAssignments($users, $classrooms, $academicYears['active']);

        // 5. Murid QA
        $students = $this->seedStudents();

        // 6. Penempatan Rombel Murid (Student Class Memberships)
        $this->seedMemberships($students, $classrooms, $academicYears);

        // 7. Catatan Pelanggaran e-Tatib
        $etatibRecords = $this->seedEtatibRecords($students, $today);

        // 8. Kasus BK (BkCase) & Penugasan Kasus & Koordinasi
        $cases = $this->seedCases($students, $users, $refs, $today, $etatibRecords);

        // 9. Tindak Lanjut Kasus (Follow-Ups)
        $this->seedFollowUps($cases, $users, $refs, $today);

        // 10. Konsultasi & Catatan Privat (Consultations & Private Notes)
        $this->seedConsultations($students, $cases, $users, $refs, $today);

        // 11. Prestasi Murid (Achievements)
        $this->seedAchievements($students, $users, $refs, $today);

        // 12. Audit Logs Operasional untuk Dashboard Aktivitas
        $this->seedAuditLogs($users, $cases, $today);
    }

    /**
     * @return array<string, ReferenceValue>
     */
    private function loadReferences(): array
    {
        $find = static fn (string $category, string $code): ReferenceValue => ReferenceValue::query()
            ->where('category', $category)
            ->where('code', $code)
            ->firstOrFail();

        return [
            'case_new' => $find('case_status', 'baru'),
            'case_in_progress' => $find('case_status', 'dalam_penanganan'),
            'case_closed' => $find('case_status', 'selesai'),

            'src_tatib' => $find('case_source', 'e_tatib'),
            'src_self' => $find('case_source', 'murid_datang_sendiri'),
            'src_found' => $find('case_source', 'temuan_guru_bk'),
            'src_referral' => $find('case_source', 'rujukan'),

            'fld_personal' => $find('service_field', 'pribadi'),
            'fld_study' => $find('service_field', 'belajar'),
            'fld_social' => $find('service_field', 'sosial'),
            'fld_career' => $find('service_field', 'karier'),

            'fu_type_consultation' => $find('follow_up_type', 'konsultasi_individual'),
            'fu_type_parent' => $find('follow_up_type', 'panggilan_orang_tua'),
            'fu_type_group' => $find('follow_up_type', 'bimbingan_kelompok'),
            'fu_type_home_visit' => $find('follow_up_type', 'kunjungan_rumah'),
            'fu_type_teacher_coord' => $find('follow_up_type', 'koordinasi_guru'),
            'fu_type_case_conference' => $find('follow_up_type', 'konferensi_kasus'),

            'fu_status_scheduled' => $find('follow_up_status', 'terjadwal'),
            'fu_status_done' => $find('follow_up_status', 'terlaksana'),
            'fu_status_postponed' => $find('follow_up_status', 'ditunda'),
            'fu_status_cancelled' => $find('follow_up_status', 'dibatalkan'),

            'coord_waiting' => $find('coordination_status', 'menunggu'),
            'coord_done' => $find('coordination_status', 'selesai'),
            'coord_cancelled' => $find('coordination_status', 'dibatalkan'),

            'cns_scheduled' => $find('consultation_status', 'dijadwalkan'),
            'cns_waiting' => $find('consultation_status', 'menunggu_konfirmasi'),
            'cns_done' => $find('consultation_status', 'terlaksana'),
            'cns_cancelled' => $find('consultation_status', 'dibatalkan'),

            'ach_type_academic' => $find('achievement_type', 'akademik'),
            'ach_type_sport' => $find('achievement_type', 'olahraga'),
            'ach_type_art' => $find('achievement_type', 'seni_budaya'),
            'ach_type_scientific' => $find('achievement_type', 'karya_ilmiah'),
            'ach_type_organization' => $find('achievement_type', 'organisasi'),

            'ach_level_school' => $find('achievement_level', 'sekolah'),
            'ach_level_city' => $find('achievement_level', 'kota_kabupaten'),
            'ach_level_province' => $find('achievement_level', 'provinsi'),
            'ach_level_national' => $find('achievement_level', 'nasional'),

            'ach_status_pending' => $find('achievement_verification_status', 'menunggu'),
            'ach_status_verified' => $find('achievement_verification_status', 'terverifikasi'),
            'ach_status_rejected' => $find('achievement_verification_status', 'ditolak'),
        ];
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(string $password): array
    {
        $accounts = [
            'guru_a' => [
                'name' => 'Guru BK QA A',
                'email' => 'guru.bk.qa.a@ruangbk.test',
                'role' => 'guru_bk',
                'is_active' => true,
            ],
            'guru_b' => [
                'name' => 'Guru BK QA B',
                'email' => 'guru.bk.qa.b@ruangbk.test',
                'role' => 'guru_bk',
                'is_active' => true,
            ],
            'guru_c' => [
                'name' => 'Guru BK QA C',
                'email' => 'guru.bk.qa.c@ruangbk.test',
                'role' => 'guru_bk',
                'is_active' => true,
            ],
            'koordinator' => [
                'name' => 'Koordinator BK QA',
                'email' => 'koordinator.bk.qa@ruangbk.test',
                'role' => 'koordinator_bk',
                'is_active' => true,
            ],
            'waka' => [
                'name' => 'Waka Kesiswaan QA',
                'email' => 'waka.kesiswaan.qa@ruangbk.test',
                'role' => 'waka_kesiswaan',
                'is_active' => true,
            ],
            'admin' => [
                'name' => 'Admin IT QA',
                'email' => 'admin.it.qa@ruangbk.test',
                'role' => 'admin_it',
                'is_active' => true,
            ],
            'guru_nonaktif' => [
                'name' => 'Guru BK QA Nonaktif',
                'email' => 'guru.bk.qa.nonaktif@ruangbk.test',
                'role' => 'guru_bk',
                'is_active' => false,
            ],
        ];

        $users = [];
        foreach ($accounts as $key => $data) {
            $role = Role::query()->where('slug', $data['role'])->firstOrFail();
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                    'email_verified_at' => now(),
                    'is_active' => $data['is_active'],
                    'deactivated_at' => $data['is_active'] ? null : now(),
                ]
            );

            $user->roles()->sync([$role->id]);
            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * @return array<string, AcademicYear>
     */
    private function seedAcademicYears(CarbonImmutable $today): array
    {
        // 2024/2025 (historis lampau)
        $ay2024 = AcademicYear::query()->updateOrCreate(
            ['name' => '2024/2025'],
            [
                'dapodik_id' => 'QA-AY-2024',
                'starts_on' => '2024-07-01',
                'ends_on' => '2025-06-30',
                'is_active' => false,
                'synced_at' => now(),
            ]
        );

        // 2025/2026 (historis transisi)
        $ay2025 = AcademicYear::query()->updateOrCreate(
            ['name' => '2025/2026'],
            [
                'dapodik_id' => 'QA-AY-2025',
                'starts_on' => '2025-07-01',
                'ends_on' => '2026-06-30',
                'is_active' => false,
                'synced_at' => now(),
            ]
        );

        // 2026/2027 (aktif berjalan — gunakan yang sudah ada jika ada, atau buat baru)
        $ay2026 = AcademicYear::query()->where('name', '2026/2027')->first();
        if (! $ay2026) {
            $ay2026 = AcademicYear::query()->create([
                'name' => '2026/2027',
                'dapodik_id' => null,
                'starts_on' => '2026-07-01',
                'ends_on' => '2027-06-30',
                'is_active' => true,
                'synced_at' => now(),
            ]);
        }

        return [
            'hist_2024' => $ay2024,
            'hist_2025' => $ay2025,
            'active' => $ay2026,
        ];
    }

    /**
     * @param array<string, AcademicYear> $years
     * @return array<string, Classroom>
     */
    private function seedClassrooms(array $years): array
    {
        $ayActive = $years['active'];
        $ayHist = $years['hist_2025'];

        // Cari atau buat X PPLG 1 (existing developer)
        $xPplg1 = Classroom::query()->where('academic_year_id', $ayActive->id)->where('name', 'X PPLG 1')->first();
        if (! $xPplg1) {
            $xPplg1 = Classroom::query()->create([
                'academic_year_id' => $ayActive->id,
                'name' => 'X PPLG 1',
                'grade_level' => 10,
                'major' => 'PPLG',
                'is_active' => true,
                'synced_at' => now(),
            ]);
        }

        $classes = [
            'x_pplg_1' => $xPplg1,
            'x_pplg_2' => Classroom::query()->updateOrCreate(
                ['academic_year_id' => $ayActive->id, 'name' => 'X PPLG 2'],
                [
                    'dapodik_id' => 'QA-CLS-XPPLG2-26',
                    'grade_level' => 10,
                    'major' => 'PPLG',
                    'is_active' => true,
                    'synced_at' => now(),
                ]
            ),
            'xi_pplg_1' => Classroom::query()->updateOrCreate(
                ['academic_year_id' => $ayActive->id, 'name' => 'XI PPLG 1'],
                [
                    'dapodik_id' => 'QA-CLS-XIPPLG1-26',
                    'grade_level' => 11,
                    'major' => 'PPLG',
                    'is_active' => true,
                    'synced_at' => now(),
                ]
            ),
            'xii_pplg_1' => Classroom::query()->updateOrCreate(
                ['academic_year_id' => $ayActive->id, 'name' => 'XII PPLG 1'],
                [
                    'dapodik_id' => 'QA-CLS-XIIPPLG1-26',
                    'grade_level' => 12,
                    'major' => 'PPLG',
                    'is_active' => true,
                    'synced_at' => now(),
                ]
            ),
            'x_tjkt_1' => Classroom::query()->updateOrCreate(
                ['academic_year_id' => $ayActive->id, 'name' => 'X TJKT 1'],
                [
                    'dapodik_id' => 'QA-CLS-XTJKT1-26',
                    'grade_level' => 10,
                    'major' => 'TJKT',
                    'is_active' => true,
                    'synced_at' => now(),
                ]
            ),
            'xi_tjkt_1' => Classroom::query()->updateOrCreate(
                ['academic_year_id' => $ayActive->id, 'name' => 'XI TJKT 1'],
                [
                    'dapodik_id' => 'QA-CLS-XITJKT1-26',
                    'grade_level' => 11,
                    'major' => 'TJKT',
                    'is_active' => true,
                    'synced_at' => now(),
                ]
            ), // Empty state: tanpa murid
            'xii_tjkt_1' => Classroom::query()->updateOrCreate(
                ['academic_year_id' => $ayActive->id, 'name' => 'XII TJKT 1'],
                [
                    'dapodik_id' => 'QA-CLS-XIITJKT1-26',
                    'grade_level' => 12,
                    'major' => 'TJKT',
                    'is_active' => true,
                    'synced_at' => now(),
                ]
            ), // Edge case: murid ada, tapi tanpa penugasan Guru BK
            'hist_x_pplg_1' => Classroom::query()->updateOrCreate(
                ['academic_year_id' => $ayHist->id, 'name' => 'X PPLG 1'],
                [
                    'dapodik_id' => 'QA-CLS-XPPLG1-25',
                    'grade_level' => 10,
                    'major' => 'PPLG',
                    'is_active' => false,
                    'synced_at' => now(),
                ]
            ),
        ];

        return $classes;
    }

    /**
     * @param array<string, User> $users
     * @param array<string, Classroom> $classrooms
     */
    private function seedTeacherAssignments(array $users, array $classrooms, AcademicYear $year): void
    {
        $koordinator = $users['koordinator'];
        $from = '2026-07-01';

        $assignments = [
            // Guru BK QA A memegang X PPLG 1 dan XI PPLG 1
            [
                'user_id' => $users['guru_a']->id,
                'classroom_id' => $classrooms['x_pplg_1']->id,
                'decision_number' => 'QA-SK-2026-001',
                'notes' => 'Penugasan Guru BK QA A untuk Kelas X PPLG 1',
            ],
            [
                'user_id' => $users['guru_a']->id,
                'classroom_id' => $classrooms['xi_pplg_1']->id,
                'decision_number' => 'QA-SK-2026-002',
                'notes' => 'Penugasan Guru BK QA A untuk Kelas XI PPLG 1',
            ],
            // Guru BK QA B memegang X PPLG 2 dan XII PPLG 1
            [
                'user_id' => $users['guru_b']->id,
                'classroom_id' => $classrooms['x_pplg_2']->id,
                'decision_number' => 'QA-SK-2026-003',
                'notes' => 'Penugasan Guru BK QA B untuk Kelas X PPLG 2',
            ],
            [
                'user_id' => $users['guru_b']->id,
                'classroom_id' => $classrooms['xii_pplg_1']->id,
                'decision_number' => 'QA-SK-2026-004',
                'notes' => 'Penugasan Guru BK QA B untuk Kelas XII PPLG 1',
            ],
        ];

        foreach ($assignments as $data) {
            TeacherAssignment::query()->updateOrCreate(
                ['decision_number' => $data['decision_number']],
                [
                    'user_id' => $data['user_id'],
                    'classroom_id' => $data['classroom_id'],
                    'academic_year_id' => $year->id,
                    'effective_from' => $from,
                    'effective_until' => null,
                    'notes' => $data['notes'],
                    'assigned_by' => $koordinator->id,
                ]
            );
        }
    }

    /**
     * @return array<int, Student>
     */
    private function seedStudents(): array
    {
        $names = [
            1 => 'Andi Pratama QA',
            2 => 'Rina Lestari QA',
            3 => 'Dimas Saputra QA',
            4 => 'Nabila Putri QA',
            5 => 'Fajar Ramadhan QA',
            6 => 'Aditya Wicaksono QA',
            7 => 'Bella Safira QA',
            8 => 'Candra Wijaya QA',
            9 => 'Dina Mariana QA',
            10 => 'Erlangga Putra QA',
            11 => 'Fitri Handayani QA',
            12 => 'Rizky Maulana QA',
            13 => 'Intan Permata QA',
            14 => 'Bagas Setiawan QA',
            15 => 'Dewi Anggraini QA',
            16 => 'Eko Prasetyo QA',
            17 => 'Gita Gutawa QA',
            18 => 'Hendra Setiawan QA',
            19 => 'Indah Permatasari QA',
            20 => 'Jefri Nichol QA',
            21 => 'Gilang Ramadhan QA', // Kasus khusus di rombel Guru B, kasus ditugaskan ke Guru A
            22 => 'Hani Safitri QA',    // Riwayat kelas tahun ajaran sebelumnya
            23 => 'Ilham Hidayat QA',   // Kelas tanpa penugasan Guru BK
            24 => 'Joko Susanto QA',    // Murid nonaktif
            25 => 'Kurnia Mega QA',     // Murid dengan prestasi pending & rejected
        ];

        $students = [];
        foreach ($names as $i => $name) {
            $nisn = sprintf('9926%06d', $i);
            $dapodikId = sprintf('QA-STU-%03d', $i);
            $isActive = ($i !== 24); // Joko Susanto dibuat nonaktif

            $students[$i] = Student::query()->updateOrCreate(
                ['nisn' => $nisn],
                [
                    'dapodik_id' => $dapodikId,
                    'name' => $name,
                    'is_active' => $isActive,
                    'synced_at' => now(),
                ]
            );
        }

        return $students;
    }

    /**
     * @param array<int, Student> $students
     * @param array<string, Classroom> $classrooms
     * @param array<string, AcademicYear> $years
     */
    private function seedMemberships(array $students, array $classrooms, array $years): void
    {
        $activeYear = $years['active'];
        $histYear = $years['hist_2025'];
        $from = '2026-07-01';

        // Pemetaan Murid ke Rombel 2026/2027
        $distribution = [
            // 1 s.d. 5 di X PPLG 1 (Guru A)
            1 => 'x_pplg_1',
            2 => 'x_pplg_1',
            3 => 'x_pplg_1',
            4 => 'x_pplg_1',
            5 => 'x_pplg_1',

            // 6 s.d. 11 di XI PPLG 1 (Guru A)
            6 => 'xi_pplg_1',
            7 => 'xi_pplg_1',
            8 => 'xi_pplg_1',
            9 => 'xi_pplg_1',
            10 => 'xi_pplg_1',
            11 => 'xi_pplg_1',

            // 12 s.d. 16 di X PPLG 2 (Guru B)
            12 => 'x_pplg_2',
            13 => 'x_pplg_2',
            14 => 'x_pplg_2',
            15 => 'x_pplg_2',
            16 => 'x_pplg_2',

            // 17 s.d. 20 di XII PPLG 1 (Guru B)
            17 => 'xii_pplg_1',
            18 => 'xii_pplg_1',
            19 => 'xii_pplg_1',
            20 => 'xii_pplg_1',

            // 21 di X PPLG 2 (Guru B)
            21 => 'x_pplg_2',

            // 22 di XI PPLG 1 (Guru A)
            22 => 'xi_pplg_1',

            // 23 di XII TJKT 1 (tanpa guru BK)
            23 => 'xii_tjkt_1',

            // 24 & 25 di X TJKT 1
            24 => 'x_tjkt_1',
            25 => 'x_tjkt_1',
        ];

        foreach ($distribution as $index => $classKey) {
            $student = $students[$index];
            $classroom = $classrooms[$classKey];
            $dapodikId = sprintf('QA-MEM-%03d', $index);

            StudentClassMembership::query()->updateOrCreate(
                ['dapodik_id' => $dapodikId],
                [
                    'student_id' => $student->id,
                    'classroom_id' => $classroom->id,
                    'academic_year_id' => $activeYear->id,
                    'effective_from' => $from,
                    'effective_until' => null,
                    'is_active' => true,
                    'synced_at' => now(),
                ]
            );
        }

        // Khusus Murid 22 (Hani Safitri): Tambahkan riwayat rombel di tahun ajaran 2025/2026
        StudentClassMembership::query()->updateOrCreate(
            ['dapodik_id' => 'QA-MEM-HIST-022'],
            [
                'student_id' => $students[22]->id,
                'classroom_id' => $classrooms['hist_x_pplg_1']->id,
                'academic_year_id' => $histYear->id,
                'effective_from' => '2025-07-01',
                'effective_until' => '2026-06-30',
                'is_active' => false,
                'synced_at' => now(),
            ]
        );
    }

    /**
     * @param array<int, Student> $students
     * @return array<string, ExternalTatibRecord>
     */
    private function seedEtatibRecords(array $students, CarbonImmutable $today): array
    {
        $records = [];

        // Dimas Saputra (#3): Terlambat masuk sekolah (5 poin) - tertaut ke kasus
        $records['dimas_terlambat'] = ExternalTatibRecord::query()->updateOrCreate(
            ['source_identifier' => 'QA-ETATIB-2026-001'],
            [
                'nisn' => $students[3]->nisn,
                'student_id' => $students[3]->id,
                'occurred_at' => $today->subDays(10)->setTime(7, 15),
                'violation_type' => 'Keterlambatan Masuk Sekolah',
                'category' => 'Kedisiplinan',
                'points' => 5,
                'source_status' => 'aktif',
                'is_active' => true,
                'source_synced_at' => now(),
                'synced_at' => now(),
            ]
        );

        // Dimas Saputra (#3): Atribut seragam tidak lengkap (5 poin) - belum ditautkan
        $records['dimas_seragam'] = ExternalTatibRecord::query()->updateOrCreate(
            ['source_identifier' => 'QA-ETATIB-2026-002'],
            [
                'nisn' => $students[3]->nisn,
                'student_id' => $students[3]->id,
                'occurred_at' => $today->subDays(4)->setTime(8, 30),
                'violation_type' => 'Atribut Seragam Tidak Lengkap',
                'category' => 'Kedisiplinan',
                'points' => 5,
                'source_status' => 'aktif',
                'is_active' => true,
                'source_synced_at' => now(),
                'synced_at' => now(),
            ]
        );

        // Rizky Maulana (#12): Meninggalkan kelas tanpa izin (15 poin) - tertaut & dikoordinasikan
        $records['rizky_bolos'] = ExternalTatibRecord::query()->updateOrCreate(
            ['source_identifier' => 'QA-ETATIB-2026-003'],
            [
                'nisn' => $students[12]->nisn,
                'student_id' => $students[12]->id,
                'occurred_at' => $today->subDays(7)->setTime(10, 0),
                'violation_type' => 'Meninggalkan Kelas Tanpa Izin',
                'category' => 'Kedisiplinan',
                'points' => 15,
                'source_status' => 'aktif',
                'is_active' => true,
                'source_synced_at' => now(),
                'synced_at' => now(),
            ]
        );

        // Andi Pratama (#1): Pelanggaran nonaktif
        $records['andi_arsip'] = ExternalTatibRecord::query()->updateOrCreate(
            ['source_identifier' => 'QA-ETATIB-2026-004'],
            [
                'nisn' => $students[1]->nisn,
                'student_id' => $students[1]->id,
                'occurred_at' => $today->subDays(45)->setTime(9, 0),
                'violation_type' => 'Pelanggaran Tata Tertib Ringan Diarsipkan',
                'category' => 'Kedisiplinan',
                'points' => 5,
                'source_status' => 'selesai',
                'is_active' => false,
                'source_synced_at' => now(),
                'synced_at' => now(),
            ]
        );

        return $records;
    }

    /**
     * @param array<int, Student> $students
     * @param array<string, User> $users
     * @param array<string, ReferenceValue> $refs
     * @param array<string, ExternalTatibRecord> $etatib
     * @return array<string, BkCase>
     */
    private function seedCases(
        array $students,
        array $users,
        array $refs,
        CarbonImmutable $today,
        array $etatib,
    ): array {
        $cases = [];

        // 1. Andi Pratama (#1, Guru A) - Kasus Baru
        $cases['case_1'] = $this->createCase(
            regNumber: 'K-QA-2026-001',
            student: $students[1],
            creator: $users['guru_a'],
            source: $refs['src_found'],
            field: $refs['fld_study'],
            status: $refs['case_new'],
            serviceDate: $today->subDays(7),
            info: 'Penurunan konsentrasi belajar dan motivasi pada mata pelajaran produktif PPLG.',
            action: 'Wawancara observasi awal dan asesmen gaya belajar.',
            internalNote: 'Catatan internal Guru A: Perlu pendampingan belajar teratur.'
        );
        $this->assignCase($cases['case_1'], $users['guru_a'], $users['guru_a'], $today->subDays(7), null, CaseAssignment::TYPE_OWNER);

        // 2. Rina Lestari (#2, Guru A) - Dalam Penanganan (Komprehensif)
        $cases['case_2'] = $this->createCase(
            regNumber: 'K-QA-2026-002',
            student: $students[2],
            creator: $users['guru_a'],
            source: $refs['src_self'],
            field: $refs['fld_personal'],
            status: $refs['case_in_progress'],
            serviceDate: $today->subDays(14),
            info: 'Murid menyampaikan keluhan stres akademik dan kendala adaptasi pertemanan.',
            action: 'Konseling individual terstruktur dan latihan teknik relaksasi pernapasan.',
            internalNote: 'Catatan internal Guru A: Rina sangat kooperatif dan menunjukkan progres positif.'
        );
        $this->assignCase($cases['case_2'], $users['guru_a'], $users['guru_a'], $today->subDays(14), null, CaseAssignment::TYPE_OWNER);

        // 3. Dimas Saputra (#3, Guru A) - Kasus dari e-Tatib
        $cases['case_3'] = $this->createCase(
            regNumber: 'K-QA-2026-003',
            student: $students[3],
            creator: $users['guru_a'],
            source: $refs['src_tatib'],
            field: $refs['fld_social'],
            status: $refs['case_in_progress'],
            serviceDate: $today->subDays(10),
            info: 'Keterlambatan berulang yang tercatat di sistem e-Tatib sekolah.',
            action: 'Klarifikasi alasan keterlambatan dan penyusunan komitmen bangun pagi.',
            internalNote: 'Catatan internal Guru A: Mengaku sering begadang karena shift kerja sampingan keluarga.'
        );
        $this->assignCase($cases['case_3'], $users['guru_a'], $users['guru_a'], $today->subDays(10), null, CaseAssignment::TYPE_OWNER);
        // Tautkan ke e-Tatib
        $cases['case_3']->etatibRecords()->syncWithoutDetaching([
            $etatib['dimas_terlambat']->id => ['linked_by' => $users['guru_a']->id],
        ]);

        // 4. Rizky Maulana (#12, Guru B) - Dikoordinasikan dengan Waka Kesiswaan QA
        $cases['case_4'] = $this->createCase(
            regNumber: 'K-QA-2026-004',
            student: $students[12],
            creator: $users['guru_b'],
            source: $refs['src_referral'],
            field: $refs['fld_social'],
            status: $refs['case_in_progress'],
            serviceDate: $today->subDays(8),
            info: 'Meninggalkan jam pelajaran tanpa izin (bolos) berdasarkan rujukan wali kelas.',
            action: 'Pemanggilan murid, klarifikasi insiden, dan koordinasi dengan bagian kesiswaan.',
            internalNote: 'Catatan rahasia Guru B: Ada indikasi ajakan teman sebaya di luar sekolah.'
        );
        $this->assignCase($cases['case_4'], $users['guru_b'], $users['guru_b'], $today->subDays(8), null, CaseAssignment::TYPE_OWNER);
        $cases['case_4']->etatibRecords()->syncWithoutDetaching([
            $etatib['rizky_bolos']->id => ['linked_by' => $users['guru_b']->id],
        ]);
        // Buat Case Coordination ke Waka QA
        CaseCoordination::query()->updateOrCreate(
            ['case_id' => $cases['case_4']->id, 'waka_user_id' => $users['waka']->id],
            [
                'status_id' => $refs['coord_waiting']->id,
                'coordination_need' => 'Memohon arahan pembinaan kesiswaan terkait pelanggaran meninggalkan kelas.',
                'result' => null,
                'recorded_by' => $users['guru_b']->id,
                'coordinated_at' => $today->subDays(6)->setTime(11, 0),
            ]
        );

        // 5. Bagas Setiawan (#14, Guru B) - Kasus Selesai
        $cases['case_5'] = $this->createCase(
            regNumber: 'K-QA-2026-005',
            student: $students[14],
            creator: $users['guru_b'],
            source: $refs['src_self'],
            field: $refs['fld_career'],
            status: $refs['case_closed'],
            serviceDate: $today->subDays(30),
            info: 'Konsultasi perencanaan karier dan pilihan magang industri perangkat lunak.',
            action: 'Eksplorasi minat bakat, review portofolio, dan pemetaan mitra industri.',
            internalNote: 'Catatan Guru B: Murid mantap memilih peminatan backend developer.',
            closed: true,
            closedAt: $today->subDays(5)
        );
        $this->assignCase($cases['case_5'], $users['guru_b'], $users['guru_b'], $today->subDays(30), null, CaseAssignment::TYPE_OWNER);

        // 6. Dewi Anggraini (#15, Guru B) - Memiliki Follow-up Overdue
        $cases['case_6'] = $this->createCase(
            regNumber: 'K-QA-2026-006',
            student: $students[15],
            creator: $users['guru_b'],
            source: $refs['src_found'],
            field: $refs['fld_study'],
            status: $refs['case_in_progress'],
            serviceDate: $today->subDays(12),
            info: 'Kerap mengantuk di kelas pada jam pelajaran pagi.',
            action: 'Konseling pola tidur dan konsultasi manajemen waktu harian.',
            internalNote: 'Catatan Guru B: Diperlukan konfirmasi ke orang tua.'
        );
        $this->assignCase($cases['case_6'], $users['guru_b'], $users['guru_b'], $today->subDays(12), null, CaseAssignment::TYPE_OWNER);

        // 7. Eko Prasetyo (#16, Guru B) - Memiliki Follow-up Hari Ini
        $cases['case_7'] = $this->createCase(
            regNumber: 'K-QA-2026-007',
            student: $students[16],
            creator: $users['guru_b'],
            source: $refs['src_referral'],
            field: $refs['fld_personal'],
            status: $refs['case_in_progress'],
            serviceDate: $today->subDays(5),
            info: 'Kendala interaksi sosial dengan teman satu kelompok praktikum.',
            action: 'Fasilitasi mediasi komunikasi dan pemahaman empati kelompok.',
            internalNote: 'Catatan Guru B: Mediasi dijadwalkan kembali hari ini.'
        );
        $this->assignCase($cases['case_7'], $users['guru_b'], $users['guru_b'], $today->subDays(5), null, CaseAssignment::TYPE_OWNER);

        // 8. Gilang Ramadhan (#21, di rombel Guru B) - Kasus ditugaskan tambahan ke Guru A
        $cases['case_8'] = $this->createCase(
            regNumber: 'K-QA-2026-008',
            student: $students[21],
            creator: $users['guru_b'],
            source: $refs['src_found'],
            field: $refs['fld_social'],
            status: $refs['case_in_progress'],
            serviceDate: $today->subDays(6),
            info: 'Kasus pendampingan lintas jurusan yang memerlukan keahlian khusus Guru BK A.',
            action: 'Penugasan kolaboratif antara Guru B dan Guru A.',
            internalNote: 'Catatan kolaboratif: Ditugaskan tambahan ke Guru BK QA A oleh Koordinator.'
        );
        $this->assignCase($cases['case_8'], $users['guru_b'], $users['guru_b'], $today->subDays(6), null, CaseAssignment::TYPE_OWNER);
        $this->assignCase($cases['case_8'], $users['guru_a'], $users['koordinator'], $today->subDays(4), null, CaseAssignment::TYPE_ADDITIONAL);

        // 9. Ilham Hidayat (#23, di rombel tanpa Guru BK) - Kasus dipegang Guru C
        $cases['case_9'] = $this->createCase(
            regNumber: 'K-QA-2026-009',
            student: $students[23],
            creator: $users['guru_c'],
            source: $refs['src_found'],
            field: $refs['fld_study'],
            status: $refs['case_new'],
            serviceDate: $today->subDays(2),
            info: 'Konsultasi pemilihan fokus uji kompetensi keahlian.',
            action: 'Asesmen awal kesiapan uji kompetensi kejuruan.',
            internalNote: 'Catatan Guru C: Memerlukan sesi lanjutan pekan depan.'
        );
        $this->assignCase($cases['case_9'], $users['guru_c'], $users['guru_c'], $today->subDays(2), null, CaseAssignment::TYPE_OWNER);

        return $cases;
    }

    private function createCase(
        string $regNumber,
        Student $student,
        User $creator,
        ReferenceValue $source,
        ReferenceValue $field,
        ReferenceValue $status,
        CarbonImmutable $serviceDate,
        string $info,
        string $action,
        string $internalNote,
        bool $closed = false,
        ?CarbonImmutable $closedAt = null,
    ): BkCase {
        return BkCase::query()->updateOrCreate(
            ['registration_number' => $regNumber],
            [
                'student_id' => $student->id,
                'temporary_student_id' => null,
                'case_source_id' => $source->id,
                'service_field_id' => $field->id,
                'status_id' => $status->id,
                'service_date' => $serviceDate,
                'referrer' => 'Petugas QA SIBK',
                'initial_info' => $info,
                'initial_action' => $action,
                'internal_note' => $internalNote,
                'final_result' => $closed ? 'Kasus telah diselesaikan dan target konseling tercapai.' : null,
                'resolution_summary' => $closed ? 'Evaluasi akhir menunjukkan kemajuan murid sesuai sasaran.' : null,
                'continued_plan' => $closed ? 'Pemantauan berkala non-formal oleh wali kelas.' : null,
                'closed_at' => $closedAt,
                'created_by' => $creator->id,
            ]
        );
    }

    private function assignCase(
        BkCase $case,
        User $teacher,
        User $assigner,
        CarbonImmutable $from,
        ?CarbonImmutable $until,
        string $type,
    ): void {
        CaseAssignment::query()->updateOrCreate(
            ['case_id' => $case->id, 'user_id' => $teacher->id, 'assignment_type' => $type],
            [
                'effective_from' => $from,
                'effective_until' => $until,
                'reason' => 'Penugasan kasus QA: '.$type,
                'assigned_by' => $assigner->id,
            ]
        );
    }

    /**
     * @param array<string, BkCase> $cases
     * @param array<string, User> $users
     * @param array<string, ReferenceValue> $refs
     */
    private function seedFollowUps(
        array $cases,
        array $users,
        array $refs,
        CarbonImmutable $today,
    ): void {
        // 1. Follow-up Mendatang (Andi Pratama - K-QA-2026-001)
        FollowUp::query()->create([
            'case_id' => $cases['case_1']->id,
            'follow_up_type_id' => $refs['fu_type_consultation']->id,
            'status_id' => $refs['fu_status_scheduled']->id,
            'planned_date' => $today->addDays(3),
            'execution_date' => null,
            'result' => null,
            'next_plan' => null,
            'recorded_by' => $users['guru_a']->id,
        ]);

        // 2. Follow-up Terlaksana (Rina Lestari - K-QA-2026-002)
        FollowUp::query()->create([
            'case_id' => $cases['case_2']->id,
            'follow_up_type_id' => $refs['fu_type_consultation']->id,
            'status_id' => $refs['fu_status_done']->id,
            'planned_date' => $today->subDays(7),
            'execution_date' => $today->subDays(6),
            'result' => 'Konseling sesi pertama terlaksana dengan hasil murid memahami sumber kecemasannya.',
            'next_plan' => 'Latihan relaksasi otot progresif mandiri selama satu pekan.',
            'recorded_by' => $users['guru_a']->id,
        ]);

        // 3. Follow-up Terjadwal berikutnya (Rina Lestari - K-QA-2026-002)
        FollowUp::query()->create([
            'case_id' => $cases['case_2']->id,
            'follow_up_type_id' => $refs['fu_type_consultation']->id,
            'status_id' => $refs['fu_status_scheduled']->id,
            'planned_date' => $today->addDays(2),
            'execution_date' => null,
            'result' => null,
            'next_plan' => null,
            'recorded_by' => $users['guru_a']->id,
        ]);

        // 4. Follow-up Ditunda (Dimas Saputra - K-QA-2026-003)
        FollowUp::query()->create([
            'case_id' => $cases['case_3']->id,
            'follow_up_type_id' => $refs['fu_type_parent']->id,
            'status_id' => $refs['fu_status_postponed']->id,
            'planned_date' => $today->subDays(1),
            'execution_date' => null,
            'result' => 'Orang tua berhalangan hadir karena keperluan dinas luar kota.',
            'next_plan' => 'Menjadwalkan ulang panggilan orang tua pada pekan depan.',
            'recorded_by' => $users['guru_a']->id,
        ]);

        // 5. Follow-up OVERDUE (Dewi Anggraini - K-QA-2026-006)
        FollowUp::query()->create([
            'case_id' => $cases['case_6']->id,
            'follow_up_type_id' => $refs['fu_type_parent']->id,
            'status_id' => $refs['fu_status_scheduled']->id,
            'planned_date' => $today->subDays(3),
            'execution_date' => null,
            'result' => null,
            'next_plan' => null,
            'recorded_by' => $users['guru_b']->id,
        ]);

        // 6. Follow-up HARI INI (Eko Prasetyo - K-QA-2026-007)
        FollowUp::query()->create([
            'case_id' => $cases['case_7']->id,
            'follow_up_type_id' => $refs['fu_type_consultation']->id,
            'status_id' => $refs['fu_status_scheduled']->id,
            'planned_date' => $today,
            'execution_date' => null,
            'result' => null,
            'next_plan' => null,
            'recorded_by' => $users['guru_b']->id,
        ]);

        // 7. Follow-up Dibatalkan (Bagas Setiawan - K-QA-2026-005)
        FollowUp::query()->create([
            'case_id' => $cases['case_5']->id,
            'follow_up_type_id' => $refs['fu_type_group']->id,
            'status_id' => $refs['fu_status_cancelled']->id,
            'planned_date' => $today->subDays(15),
            'execution_date' => null,
            'result' => 'Dibatalkan karena permasalahan karier sudah terselesaikan melalui bimbingan individual.',
            'next_plan' => null,
            'recorded_by' => $users['guru_b']->id,
        ]);
    }

    /**
     * @param array<int, Student> $students
     * @param array<string, BkCase> $cases
     * @param array<string, User> $users
     * @param array<string, ReferenceValue> $refs
     */
    private function seedConsultations(
        array $students,
        array $cases,
        array $users,
        array $refs,
        CarbonImmutable $today,
    ): void {
        // 1. Konsultasi Terhubung Kasus (Rina Lestari - KNS-QA-2026-001)
        $c1 = Consultation::query()->updateOrCreate(
            ['registration_number' => 'KNS-QA-2026-001'],
            [
                'student_id' => $students[2]->id,
                'case_id' => $cases['case_2']->id,
                'service_field_id' => $refs['fld_personal']->id,
                'status_id' => $refs['cns_done']->id,
                'topic' => 'Konsultasi Manajemen Stres dan Kecemasan Belajar',
                'referral_source' => 'Murid datang mandiri',
                'session_date' => $today->subDays(6),
                'starts_at' => '09:00:00',
                'ends_at' => '09:45:00',
                'follow_up_date' => $today->addDays(2),
                'general_summary' => 'Sesi konseling individual membahas strategi adaptasi belajar dan pertemanan sebaya.',
                'counselor_id' => $users['guru_a']->id,
            ]
        );

        ConsultationPrivateNote::query()->updateOrCreate(
            ['consultation_id' => $c1->id],
            [
                'internal_note' => 'Catatan Rahasia Guru BK QA A: Murid mengalami tekanan ekspektasi nilai akademik dari keluarga.',
                'sensitive_content' => 'Informasi pribadi sensitif mengenai kondisi komunikasi orang tua dan anak.',
                'conclusion' => 'Memerlukan latihan regulasi emosi dan relaksasi mandiri.',
                'follow_up_plan' => 'Evaluasi jurnal harian dan latihan pernapasan sebelum ujian.',
                'updated_by' => $users['guru_a']->id,
            ]
        );

        // 2. Konsultasi Mandiri Tanpa Kasus (Intan Permata - KNS-QA-2026-002)
        $c2 = Consultation::query()->updateOrCreate(
            ['registration_number' => 'KNS-QA-2026-002'],
            [
                'student_id' => $students[13]->id,
                'case_id' => null,
                'service_field_id' => $refs['fld_career']->id,
                'status_id' => $refs['cns_done']->id,
                'topic' => 'Konsultasi Minat Bakat dan Peluang Studi Lanjut PTN',
                'referral_source' => 'Inisiatif murid',
                'session_date' => $today->subDays(4),
                'starts_at' => '10:15:00',
                'ends_at' => '11:00:00',
                'follow_up_date' => null,
                'general_summary' => 'Diskusi pemilihan jurusan kuliah teknik informatika dan peluang beasiswa prestasi.',
                'counselor_id' => $users['guru_b']->id,
            ]
        );

        ConsultationPrivateNote::query()->updateOrCreate(
            ['consultation_id' => $c2->id],
            [
                'internal_note' => 'Catatan Rahasia Guru BK QA B: Minat sangat tinggi di bidang UI/UX, potensi nilai raport mendukung SNBP.',
                'sensitive_content' => 'Kondisi ekonomi keluarga membutuhkan bantuan program beasiswa penuh.',
                'conclusion' => 'Direkomendasikan mendaftar program KIP Kuliah dan jalur prestasi.',
                'follow_up_plan' => 'Membantu persiapan dokumen portofolio desain.',
                'updated_by' => $users['guru_b']->id,
            ]
        );

        // 3. Konsultasi Terjadwal (Andi Pratama - KNS-QA-2026-003)
        Consultation::query()->updateOrCreate(
            ['registration_number' => 'KNS-QA-2026-003'],
            [
                'student_id' => $students[1]->id,
                'case_id' => $cases['case_1']->id,
                'service_field_id' => $refs['fld_study']->id,
                'status_id' => $refs['cns_scheduled']->id,
                'topic' => 'Sesi Evaluasi Jadwal Belajar Mandiri',
                'referral_source' => 'Tindak lanjut kasus',
                'session_date' => $today->addDays(3),
                'starts_at' => '13:00:00',
                'ends_at' => '13:45:00',
                'follow_up_date' => null,
                'general_summary' => 'Rencana evaluasi jadwal belajar terstruktur.',
                'counselor_id' => $users['guru_a']->id,
            ]
        );
    }

    /**
     * @param array<int, Student> $students
     * @param array<string, User> $users
     * @param array<string, ReferenceValue> $refs
     */
    private function seedAchievements(
        array $students,
        array $users,
        array $refs,
        CarbonImmutable $today,
    ): void {
        // 1. Nabila Putri (#4, Guru A): Terverifikasi (Kota/Kabupaten)
        Achievement::query()->updateOrCreate(
            ['evidence_reference' => 'QA-EVD-2026-001'],
            [
                'student_id' => $students[4]->id,
                'type_id' => $refs['ach_type_academic']->id,
                'level_id' => $refs['ach_level_city']->id,
                'activity_name' => 'QA Lomba Kompetensi Siswa Web Technologies Kota Surabaya',
                'organizer' => 'Dinas Pendidikan Kota Surabaya',
                'achievement_date' => $today->subDays(20),
                'result' => 'Juara 1',
                'evidence_description' => 'Piagam Penghargaan dan Surat Keputusan Pemenang LKS.',
                'notes' => 'Prestasi membanggakan di bidang rekayasa web.',
                'verification_status_id' => $refs['ach_status_verified']->id,
                'recorded_by' => $users['guru_a']->id,
                'reviewer_id' => $users['koordinator']->id,
                'reviewed_at' => $today->subDays(15),
                'verification_notes' => 'Berkas sertifikat fisik dan legalisir panitia telah diverifikasi sah.',
            ]
        );

        // 2. Nabila Putri (#4, Guru A): Menunggu Verifikasi (Provinsi)
        Achievement::query()->updateOrCreate(
            ['evidence_reference' => 'QA-EVD-2026-002'],
            [
                'student_id' => $students[4]->id,
                'type_id' => $refs['ach_type_academic']->id,
                'level_id' => $refs['ach_level_province']->id,
                'activity_name' => 'QA LKS Web Technologies Tingkat Jawa Timur',
                'organizer' => 'Dinas Pendidikan Provinsi Jawa Timur',
                'achievement_date' => $today->subDays(5),
                'result' => 'Juara 2',
                'evidence_description' => 'Sertifikat digital dan medali perak.',
                'notes' => 'Menunggu verifikasi dari Koordinator BK.',
                'verification_status_id' => $refs['ach_status_pending']->id,
                'recorded_by' => $users['guru_a']->id,
                'reviewer_id' => null,
                'reviewed_at' => null,
                'verification_notes' => null,
            ]
        );

        // 3. Nabila Putri (#4, Guru A): Ditolak (Nasional)
        Achievement::query()->updateOrCreate(
            ['evidence_reference' => 'QA-EVD-2026-003'],
            [
                'student_id' => $students[4]->id,
                'type_id' => $refs['ach_type_scientific']->id,
                'level_id' => $refs['ach_level_national']->id,
                'activity_name' => 'QA Lomba Karya Ilmiah Remaja Nasional',
                'organizer' => 'Komunitas Independen Sains',
                'achievement_date' => $today->subDays(40),
                'result' => 'Peserta Favorit',
                'evidence_description' => 'E-sertifikat keikutsertaan.',
                'notes' => 'Pengajuan ditolak karena penyelenggara tidak memenuhi standar akreditasi kompetisi.',
                'verification_status_id' => $refs['ach_status_rejected']->id,
                'recorded_by' => $users['guru_a']->id,
                'reviewer_id' => $users['koordinator']->id,
                'reviewed_at' => $today->subDays(35),
                'verification_notes' => 'Kompetisi bukan diselenggarakan oleh lembaga kedinasan resmi.',
            ]
        );

        // 4. Rizky Maulana (#12, Guru B): Terverifikasi (Olahraga) - DAPAT DILIHAT OLEH WAKA KESISWAAN
        Achievement::query()->updateOrCreate(
            ['evidence_reference' => 'QA-EVD-2026-004'],
            [
                'student_id' => $students[12]->id,
                'type_id' => $refs['ach_type_sport']->id,
                'level_id' => $refs['ach_level_city']->id,
                'activity_name' => 'QA Turnamen Futsal Pelajar Antar-SMK Surabaya',
                'organizer' => 'Dispora Kota Surabaya',
                'achievement_date' => $today->subDays(25),
                'result' => 'Juara 1 & Top Scorer',
                'evidence_description' => 'Sertifikat dan piala turnamen futsal.',
                'notes' => 'Murid memiliki potensi besar di bidang keolahragaan futsal.',
                'verification_status_id' => $refs['ach_status_verified']->id,
                'recorded_by' => $users['guru_b']->id,
                'reviewer_id' => $users['koordinator']->id,
                'reviewed_at' => $today->subDays(20),
                'verification_notes' => 'Sertifikat dan dokumentasi resmi sah.',
            ]
        );

        // 5. Rizky Maulana (#12, Guru B): Menunggu Verifikasi (Seni) - TIDAK DAPAT DILIHAT OLEH WAKA
        Achievement::query()->updateOrCreate(
            ['evidence_reference' => 'QA-EVD-2026-005'],
            [
                'student_id' => $students[12]->id,
                'type_id' => $refs['ach_type_art']->id,
                'level_id' => $refs['ach_level_school']->id,
                'activity_name' => 'QA Festival Seni Teater Sekolah',
                'organizer' => 'OSIS SMKN 1 Surabaya',
                'achievement_date' => $today->subDays(3),
                'result' => 'Pemeran Pria Terbaik',
                'evidence_description' => 'Piagam apresiasi pentas seni.',
                'notes' => 'Menunggu verifikasi prestasi tingkat sekolah.',
                'verification_status_id' => $refs['ach_status_pending']->id,
                'recorded_by' => $users['guru_b']->id,
                'reviewer_id' => null,
                'reviewed_at' => null,
                'verification_notes' => null,
            ]
        );

        // 6. Kurnia Mega (#25, Guru A): Terverifikasi (Organisasi)
        Achievement::query()->updateOrCreate(
            ['evidence_reference' => 'QA-EVD-2026-006'],
            [
                'student_id' => $students[25]->id,
                'type_id' => $refs['ach_type_organization']->id,
                'level_id' => $refs['ach_level_school']->id,
                'activity_name' => 'QA Kepengurusan Ketua Divisi Kepemimpinan OSIS',
                'organizer' => 'SMKN 1 Surabaya',
                'achievement_date' => $today->subDays(60),
                'result' => 'Pengurus Terbaik Periode 2025/2026',
                'evidence_description' => 'Surat Keputusan Kepala Sekolah.',
                'notes' => 'Kinerja kepemimpinan organisasi sangat aktif.',
                'verification_status_id' => $refs['ach_status_verified']->id,
                'recorded_by' => $users['guru_a']->id,
                'reviewer_id' => $users['koordinator']->id,
                'reviewed_at' => $today->subDays(50),
                'verification_notes' => 'Terverifikasi sesuai SK Kepala Sekolah.',
            ]
        );
    }

    /**
     * @param array<string, User> $users
     * @param array<string, BkCase> $cases
     */
    private function seedAuditLogs(array $users, array $cases, CarbonImmutable $today): void
    {
        $logs = [
            [
                'actor_id' => $users['guru_a']->id,
                'action' => 'case.created',
                'auditable_type' => BkCase::class,
                'auditable_id' => $cases['case_1']->id,
                'summary' => 'Kasus baru K-QA-2026-001 dibuka untuk murid Andi Pratama.',
                'created_at' => $today->subDays(7)->setTime(8, 30),
            ],
            [
                'actor_id' => $users['guru_a']->id,
                'action' => 'consultation.created',
                'auditable_type' => Consultation::class,
                'auditable_id' => 1,
                'summary' => 'Konsultasi individual KNS-QA-2026-001 dicatat oleh Guru BK QA A.',
                'created_at' => $today->subDays(6)->setTime(10, 0),
            ],
            [
                'actor_id' => $users['guru_b']->id,
                'action' => 'case.coordinated',
                'auditable_type' => BkCase::class,
                'auditable_id' => $cases['case_4']->id,
                'summary' => 'Kasus K-QA-2026-004 dikoordinasikan kepada Waka Kesiswaan.',
                'created_at' => $today->subDays(6)->setTime(11, 15),
            ],
            [
                'actor_id' => $users['koordinator']->id,
                'action' => 'achievement.verified',
                'auditable_type' => Achievement::class,
                'auditable_id' => 1,
                'summary' => 'Prestasi LKS Web Technologies diverifikasi oleh Koordinator BK.',
                'created_at' => $today->subDays(15)->setTime(14, 0),
            ],
            [
                'actor_id' => $users['admin']->id,
                'action' => 'account.updated',
                'auditable_type' => User::class,
                'auditable_id' => $users['guru_b']->id,
                'summary' => 'Admin IT memverifikasi hak akses pengguna Guru BK QA B.',
                'created_at' => $today->subDays(20)->setTime(9, 0),
            ],
        ];

        foreach ($logs as $log) {
            AuditLog::query()->create([
                'actor_id' => $log['actor_id'],
                'action' => $log['action'],
                'auditable_type' => $log['auditable_type'],
                'auditable_id' => $log['auditable_id'],
                'summary' => $log['summary'],
                'after_values' => ['qa_dataset' => true],
                'created_at' => $log['created_at'],
                'updated_at' => $log['created_at'],
            ]);
        }
    }
}
