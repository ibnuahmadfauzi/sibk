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
use App\Models\Correction;
use App\Models\ExternalTatibRecord;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\AuthorizationScenarioCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AuthorizationScenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new InvalidArgumentException('Dataset penelitian RBAC hanya boleh dibuat pada environment local atau testing.');
        }

        $password = (string) config('sibk.seed_accounts.password');
        if (mb_strlen($password) < 8) {
            throw new InvalidArgumentException('SIBK_SEED_ACCOUNT_PASSWORD minimal terdiri dari 8 karakter.');
        }

        $this->call([RoleSeeder::class, ReferenceSeeder::class]);

        DB::transaction(function () use ($password): void {
            $this->removeExistingScenario();
            $this->createScenario($password);
        });
    }

    private function removeExistingScenario(): void
    {
        $emails = array_column(AuthorizationScenarioCatalog::actors(), 'email');
        $userIds = DB::table('users')->whereIn('email', $emails)->pluck('id');
        $studentIds = DB::table('students')->where('dapodik_id', 'like', 'RBAC-STUDENT-%')->pluck('id');
        $caseIds = DB::table('cases')->where('registration_number', 'like', 'K-RBAC-%')
            ->orWhereIn('created_by', $userIds)->pluck('id');
        $consultationIds = DB::table('consultations')->where('registration_number', 'like', 'KNS-RBAC-%')
            ->orWhereIn('counselor_id', $userIds)->pluck('id');
        $achievementIds = DB::table('achievements')->where('activity_name', 'like', 'RBAC-%')
            ->orWhereIn('recorded_by', $userIds)->pluck('id');
        $temporaryStudentIds = DB::table('temporary_students')->whereIn('created_by', $userIds)->pluck('id');

        DB::table('audit_logs')->whereIn('actor_id', $userIds)->delete();
        DB::table('user_notifications')->whereIn('user_id', $userIds)
            ->orWhere('deduplication_key', 'like', 'RBAC-%')->delete();
        DB::table('corrections')->where('registration_number', 'like', 'KR-RBAC-%')
            ->orWhereIn('requester_id', $userIds)->delete();
        DB::table('consultation_private_notes')->whereIn('consultation_id', $consultationIds)->delete();
        DB::table('consultations')->whereIn('id', $consultationIds)->delete();
        DB::table('case_etatib_links')->whereIn('case_id', $caseIds)->delete();
        DB::table('follow_ups')->whereIn('case_id', $caseIds)->delete();
        DB::table('case_coordinations')->whereIn('case_id', $caseIds)->delete();
        DB::table('case_assignments')->whereIn('case_id', $caseIds)->delete();
        DB::table('cases')->whereIn('id', $caseIds)->delete();
        DB::table('achievements')->whereIn('id', $achievementIds)->delete();
        DB::table('identity_reconciliations')->whereIn('temporary_student_id', $temporaryStudentIds)->delete();
        DB::table('temporary_students')->whereIn('id', $temporaryStudentIds)->delete();
        DB::table('external_tatib_records')->where('source_identifier', 'like', 'RBAC-ETATIB-%')->delete();
        DB::table('teacher_assignments')->where('decision_number', 'like', 'RBAC-SK-%')
            ->orWhereIn('assigned_by', $userIds)->delete();
        DB::table('student_class_memberships')->where('dapodik_id', 'like', 'RBAC-MEMBERSHIP-%')->delete();
        DB::table('students')->whereIn('id', $studentIds)->delete();
        DB::table('classrooms')->where('dapodik_id', 'like', 'RBAC-CLASS-%')->delete();
        DB::table('academic_years')->where('dapodik_id', 'RBAC-ACADEMIC-YEAR')->delete();
        DB::table('users')->whereIn('id', $userIds)->delete();
    }

    private function createScenario(string $password): void
    {
        $today = CarbonImmutable::today();
        $start = $today->subDays(180);
        $end = $today->addDays(180);

        $actors = $this->createActors($password);
        $year = AcademicYear::query()->create([
            'dapodik_id' => 'RBAC-ACADEMIC-YEAR',
            'name' => 'RBAC '.$start->format('Y').'/'.$end->format('Y'),
            'starts_on' => $start,
            'ends_on' => $end,
            'is_active' => true,
            'synced_at' => now(),
        ]);
        $classes = [
            'a' => $this->classroom($year, 'RBAC-CLASS-A', 'RBAC XII-A'),
            'b' => $this->classroom($year, 'RBAC-CLASS-B', 'RBAC XII-B'),
            'multi' => $this->classroom($year, 'RBAC-CLASS-MULTI', 'RBAC XII-M'),
        ];

        $this->teacherAssignment($actors['guru_a'], $classes['a'], $year, $actors['koordinator'], $start, null, 'RBAC-SK-AKTIF-A');
        $this->teacherAssignment($actors['guru_b'], $classes['b'], $year, $actors['koordinator'], $start, $today->addDays(89), 'RBAC-SK-AKTIF-B');
        $this->teacherAssignment($actors['guru_a'], $classes['b'], $year, $actors['koordinator'], $today->addDays(90), $end, 'RBAC-SK-MASA-DEPAN');
        $this->teacherAssignment($actors['guru_a'], $classes['multi'], $year, $actors['koordinator'], $start, $today->subDay(), 'RBAC-SK-KEDALUWARSA');
        $this->teacherAssignment($actors['multi_role'], $classes['multi'], $year, $actors['koordinator'], $today, null, 'RBAC-SK-AKTIF-MULTI');

        $students = [
            'a' => $this->student('RBAC-STUDENT-A', '9900000001', 'RBAC Murid Alpha'),
            'b' => $this->student('RBAC-STUDENT-B', '9900000002', 'RBAC Murid Beta'),
            'multi' => $this->student('RBAC-STUDENT-MULTI', '9900000003', 'RBAC Murid Gamma'),
            'future' => $this->student('RBAC-STUDENT-FUTURE', '9900000004', 'RBAC Murid Delta'),
        ];
        $this->membership($students['a'], $classes['a'], $year, $start, null, 'RBAC-MEMBERSHIP-A');
        $this->membership($students['b'], $classes['b'], $year, $start, null, 'RBAC-MEMBERSHIP-B');
        $this->membership($students['future'], $classes['b'], $year, $start, null, 'RBAC-MEMBERSHIP-FUTURE');
        $this->membership($students['multi'], $classes['a'], $year, $start, $today->subDay(), 'RBAC-MEMBERSHIP-MOVED-OLD');
        $this->membership($students['multi'], $classes['multi'], $year, $today, null, 'RBAC-MEMBERSHIP-MOVED-NEW');

        $references = $this->references();
        $cases = [
            'a' => $this->case('K-RBAC-001', $students['a'], $actors['guru_a'], $references, $today->subDays(20), 'RBAC-INTERNAL-CASE-A'),
            'b' => $this->case('K-RBAC-002', $students['b'], $actors['guru_b'], $references, $today->subDays(15), 'RBAC-INTERNAL-CASE-B'),
            'special' => $this->case('K-RBAC-003', $students['b'], $actors['guru_b'], $references, $today->subDays(10), 'RBAC-INTERNAL-CASE-SPECIAL'),
            'closed' => $this->case('K-RBAC-004', $students['a'], $actors['guru_a'], $references, $today->subDays(60), 'RBAC-INTERNAL-CASE-CLOSED', true),
            'future' => $this->case('K-RBAC-005', $students['future'], $actors['guru_b'], $references, $today->subDays(4), 'RBAC-INTERNAL-CASE-FUTURE'),
        ];
        $this->caseAssignment($cases['a'], $actors['guru_a'], $actors['guru_a'], $start, null, CaseAssignment::TYPE_OWNER, 'RBAC pemilik kasus Alpha.');
        $this->caseAssignment($cases['b'], $actors['guru_b'], $actors['guru_b'], $start, null, CaseAssignment::TYPE_OWNER, 'RBAC pemilik kasus Beta.');
        $this->caseAssignment($cases['special'], $actors['guru_b'], $actors['guru_b'], $start, null, CaseAssignment::TYPE_OWNER, 'RBAC pemilik kasus khusus.');
        $this->caseAssignment($cases['special'], $actors['guru_a'], $actors['koordinator'], $today->subDays(5), null, CaseAssignment::TYPE_ADDITIONAL, 'RBAC penugasan khusus aktif.');
        $this->caseAssignment($cases['closed'], $actors['guru_a'], $actors['guru_a'], $start, null, CaseAssignment::TYPE_OWNER, 'RBAC histori kasus selesai.');
        $this->caseAssignment($cases['future'], $actors['guru_b'], $actors['guru_b'], $start, null, CaseAssignment::TYPE_OWNER, 'RBAC kasus pembanding penugasan masa depan.');

        $coordination = CaseCoordination::query()->create([
            'case_id' => $cases['b']->id,
            'waka_user_id' => $actors['waka_a']->id,
            'status_id' => $references['coordination_waiting']->id,
            'coordination_need' => 'RBAC kebutuhan koordinasi sintetis.',
            'recorded_by' => $actors['guru_b']->id,
            'coordinated_at' => now()->subDays(3),
        ]);

        $followUps = [
            'a' => FollowUp::query()->create([
                'case_id' => $cases['a']->id,
                'follow_up_type_id' => $references['follow_up_type']->id,
                'status_id' => $references['follow_up_scheduled']->id,
                'planned_date' => $today->addDays(2),
                'recorded_by' => $actors['guru_a']->id,
            ]),
            'b' => FollowUp::query()->create([
                'case_id' => $cases['b']->id,
                'follow_up_type_id' => $references['follow_up_type']->id,
                'status_id' => $references['follow_up_done']->id,
                'planned_date' => $today->subDays(2),
                'execution_date' => $today->subDay(),
                'result' => 'RBAC hasil tindak lanjut privat Beta.',
                'next_plan' => 'RBAC rencana privat Beta.',
                'recorded_by' => $actors['guru_b']->id,
            ]),
        ];

        $etatib = ExternalTatibRecord::query()->create([
            'source_identifier' => 'RBAC-ETATIB-B-001',
            'nisn' => $students['b']->nisn,
            'student_id' => $students['b']->id,
            'occurred_at' => now()->subDays(14),
            'violation_type' => 'RBAC pelanggaran sintetis',
            'category' => 'RBAC Kedisiplinan',
            'points' => 10,
            'source_status' => 'aktif',
            'is_active' => true,
            'source_synced_at' => now(),
            'synced_at' => now(),
        ]);
        DB::table('case_etatib_links')->insert([
            'case_id' => $cases['b']->id,
            'external_tatib_record_id' => $etatib->id,
            'linked_by' => $actors['guru_b']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $consultations = [
            'a' => $this->consultation('KNS-RBAC-001', $students['a'], $cases['a'], $actors['guru_a'], $references, $today->subDays(8), 'RBAC ringkasan umum Alpha.'),
            'b' => $this->consultation('KNS-RBAC-002', $students['b'], $cases['b'], $actors['guru_b'], $references, $today->subDays(7), 'RBAC ringkasan umum Beta.'),
            'history' => $this->consultation('KNS-RBAC-003', $students['multi'], null, $actors['guru_a'], $references, $today->subDays(30), 'RBAC ringkasan histori perpindahan.'),
        ];
        $this->privateNote($consultations['a'], $actors['guru_a'], 'RBAC-PRIVATE-A');
        $this->privateNote($consultations['b'], $actors['guru_b'], 'RBAC-PRIVATE-B');
        $this->privateNote($consultations['history'], $actors['guru_a'], 'RBAC-PRIVATE-HISTORY');

        $verifiedAchievement = Achievement::query()->create([
            'student_id' => $students['b']->id,
            'type_id' => $references['achievement_type']->id,
            'level_id' => $references['achievement_level']->id,
            'activity_name' => 'RBAC-Prestasi-Terverifikasi',
            'organizer' => 'RBAC Penyelenggara Sintetis',
            'achievement_date' => $today->subDays(25),
            'result' => 'Juara 1 Sintetis',
            'evidence_reference' => 'RBAC-ARSIP-001',
            'notes' => 'RBAC catatan prestasi privat.',
            'verification_status_id' => $references['achievement_verified']->id,
            'recorded_by' => $actors['guru_b']->id,
            'reviewer_id' => $actors['koordinator']->id,
            'reviewed_at' => now()->subDays(20),
            'verification_notes' => 'RBAC catatan pemeriksaan.',
        ]);
        $pendingAchievement = Achievement::query()->create([
            'student_id' => $students['b']->id,
            'type_id' => $references['achievement_type']->id,
            'level_id' => $references['achievement_level']->id,
            'activity_name' => 'RBAC-Prestasi-Menunggu',
            'organizer' => 'RBAC Penyelenggara Sintetis',
            'achievement_date' => $today->subDays(5),
            'result' => 'Finalis Sintetis',
            'evidence_reference' => 'RBAC-ARSIP-002',
            'verification_status_id' => $references['achievement_pending']->id,
            'recorded_by' => $actors['guru_b']->id,
        ]);

        Correction::query()->create([
            'registration_number' => 'KR-RBAC-001',
            'correction_type' => Correction::TYPE_OPERATIONAL,
            'target_type' => BkCase::class,
            'target_id' => $cases['a']->id,
            'target_label' => 'RBAC kasus Alpha',
            'field_name' => 'initial_action',
            'field_label' => 'Penanganan Awal',
            'old_value' => 'RBAC asesmen awal.',
            'proposed_value' => 'RBAC asesmen awal diperbarui.',
            'reason' => 'RBAC alasan koreksi operasional.',
            'status_id' => $references['correction_pending']->id,
            'requester_id' => $actors['guru_a']->id,
        ]);
        Correction::query()->create([
            'registration_number' => 'KR-RBAC-002',
            'correction_type' => Correction::TYPE_MASTER,
            'target_type' => Student::class,
            'target_id' => $students['a']->id,
            'target_label' => 'RBAC murid Alpha',
            'field_name' => 'name',
            'field_label' => 'Nama Resmi',
            'old_value' => $students['a']->name,
            'proposed_value' => 'RBAC Murid Alpha Terkoreksi',
            'reason' => 'RBAC laporan koreksi ke sumber resmi.',
            'status_id' => $references['correction_pending']->id,
            'requester_id' => $actors['multi_role']->id,
        ]);

        $notifications = [
            'a' => UserNotification::query()->create([
                'user_id' => $actors['guru_a']->id,
                'category' => UserNotification::CATEGORY_ASSIGNMENT,
                'title' => 'RBAC Notifikasi Guru A',
                'message' => 'RBAC notifikasi sintetis milik Guru A.',
                'target_type' => BkCase::class,
                'target_id' => $cases['special']->id,
                'action_route' => 'cases.show',
                'action_parameters' => ['case' => $cases['special']->id],
                'deduplication_key' => 'RBAC-NOTIFICATION-A',
            ]),
            'b' => UserNotification::query()->create([
                'user_id' => $actors['guru_b']->id,
                'category' => UserNotification::CATEGORY_COORDINATION,
                'title' => 'RBAC Notifikasi Guru B',
                'message' => 'RBAC notifikasi sintetis milik Guru B.',
                'target_type' => BkCase::class,
                'target_id' => $cases['b']->id,
                'action_route' => 'cases.show',
                'action_parameters' => ['case' => $cases['b']->id],
                'deduplication_key' => 'RBAC-NOTIFICATION-B',
            ]),
        ];

        foreach ([
            [$actors['koordinator'], 'rbac.scenario.created', $year, 'Dataset penelitian RBAC dibuat.'],
            [$actors['guru_a'], 'case.created', $cases['a'], 'Kasus sintetis RBAC Alpha tersedia.'],
            [$actors['guru_b'], 'case.coordinated', $cases['b'], 'Kasus sintetis RBAC dikoordinasikan.'],
            [$actors['koordinator'], 'achievement.verified', $verifiedAchievement, 'Prestasi sintetis RBAC diverifikasi.'],
        ] as [$actor, $action, $auditable, $summary]) {
            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'action' => $action,
                'auditable_type' => $auditable::class,
                'auditable_id' => $auditable->id,
                'summary' => $summary,
                'after_values' => ['scenario' => true, 'resource_id' => $auditable->id],
            ]);
        }

        unset($coordination, $followUps, $pendingAchievement, $notifications);
    }

    /** @return array<string, User> */
    private function createActors(string $password): array
    {
        $actors = [];
        foreach (AuthorizationScenarioCatalog::actors() as $key => $definition) {
            $user = User::query()->create([
                'name' => $definition['name'],
                'email' => $definition['email'],
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => $definition['active'],
                'deactivated_at' => $definition['active'] ? null : now(),
            ]);
            $roleIds = Role::query()->whereIn('slug', $definition['roles'])->pluck('id');
            if ($roleIds->count() !== count($definition['roles'])) {
                throw new InvalidArgumentException('Role dataset penelitian belum lengkap.');
            }
            $user->roles()->sync($roleIds);
            $actors[$key] = $user;
        }

        return $actors;
    }

    private function classroom(AcademicYear $year, string $dapodikId, string $name): Classroom
    {
        return Classroom::query()->create([
            'dapodik_id' => $dapodikId,
            'academic_year_id' => $year->id,
            'name' => $name,
            'grade_level' => 12,
            'major' => 'RBAC Program Sintetis',
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function student(string $dapodikId, string $nisn, string $name): Student
    {
        return Student::query()->create([
            'dapodik_id' => $dapodikId,
            'nisn' => $nisn,
            'name' => $name,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function membership(Student $student, Classroom $classroom, AcademicYear $year, CarbonImmutable $from, ?CarbonImmutable $until, string $dapodikId): void
    {
        StudentClassMembership::query()->create([
            'dapodik_id' => $dapodikId,
            'student_id' => $student->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => $from,
            'effective_until' => $until,
            'is_active' => true,
            'synced_at' => now(),
        ]);
    }

    private function teacherAssignment(User $teacher, Classroom $classroom, AcademicYear $year, User $assigner, CarbonImmutable $from, ?CarbonImmutable $until, string $decision): void
    {
        TeacherAssignment::query()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $year->id,
            'effective_from' => $from,
            'effective_until' => $until,
            'decision_number' => $decision,
            'notes' => 'RBAC data sintetis penelitian.',
            'assigned_by' => $assigner->id,
        ]);
    }

    /** @param array<string, ReferenceValue> $references */
    private function case(string $number, Student $student, User $creator, array $references, CarbonImmutable $date, string $internalNote, bool $closed = false): BkCase
    {
        return BkCase::query()->create([
            'registration_number' => $number,
            'student_id' => $student->id,
            'case_source_id' => $references['case_source']->id,
            'service_field_id' => $references['service_field']->id,
            'status_id' => ($closed ? $references['case_closed'] : $references['case_new'])->id,
            'service_date' => $date,
            'referrer' => 'RBAC pengujian penelitian',
            'initial_info' => 'RBAC informasi awal sintetis.',
            'initial_action' => 'RBAC asesmen awal.',
            'internal_note' => $internalNote,
            'final_result' => $closed ? 'RBAC hasil akhir sintetis.' : null,
            'resolution_summary' => $closed ? 'RBAC ringkasan penyelesaian.' : null,
            'continued_plan' => $closed ? 'RBAC rencana lanjutan.' : null,
            'closed_at' => $closed ? $date->addDays(10) : null,
            'created_by' => $creator->id,
        ]);
    }

    private function caseAssignment(BkCase $case, User $teacher, User $assigner, CarbonImmutable $from, ?CarbonImmutable $until, string $type, string $reason): void
    {
        CaseAssignment::query()->create([
            'case_id' => $case->id,
            'user_id' => $teacher->id,
            'assignment_type' => $type,
            'effective_from' => $from,
            'effective_until' => $until,
            'reason' => $reason,
            'assigned_by' => $assigner->id,
        ]);
    }

    /** @param array<string, ReferenceValue> $references */
    private function consultation(string $number, Student $student, ?BkCase $case, User $counselor, array $references, CarbonImmutable $date, string $summary): Consultation
    {
        return Consultation::query()->create([
            'registration_number' => $number,
            'student_id' => $student->id,
            'case_id' => $case?->id,
            'service_field_id' => $references['service_field']->id,
            'status_id' => $references['consultation_done']->id,
            'topic' => 'RBAC topik konsultasi sintetis',
            'referral_source' => 'RBAC penelitian',
            'session_date' => $date,
            'starts_at' => '09:00:00',
            'ends_at' => '09:45:00',
            'general_summary' => $summary,
            'counselor_id' => $counselor->id,
        ]);
    }

    private function privateNote(Consultation $consultation, User $counselor, string $marker): void
    {
        ConsultationPrivateNote::query()->create([
            'consultation_id' => $consultation->id,
            'internal_note' => $marker.' INTERNAL',
            'sensitive_content' => $marker.' SENSITIVE',
            'conclusion' => $marker.' CONCLUSION',
            'follow_up_plan' => $marker.' PLAN',
            'updated_by' => $counselor->id,
        ]);
    }

    /** @return array<string, ReferenceValue> */
    private function references(): array
    {
        $find = static fn (string $category, string $code): ReferenceValue => ReferenceValue::query()
            ->where('category', $category)->where('code', $code)->firstOrFail();

        return [
            'case_source' => $find('case_source', 'temuan_guru_bk'),
            'service_field' => $find('service_field', 'pribadi'),
            'case_new' => $find('case_status', 'baru'),
            'case_closed' => $find('case_status', 'selesai'),
            'coordination_waiting' => $find('coordination_status', 'menunggu'),
            'follow_up_type' => $find('follow_up_type', 'konsultasi_individual'),
            'follow_up_scheduled' => $find('follow_up_status', 'terjadwal'),
            'follow_up_done' => $find('follow_up_status', 'terlaksana'),
            'consultation_done' => $find('consultation_status', 'terlaksana'),
            'achievement_type' => $find('achievement_type', 'akademik'),
            'achievement_level' => $find('achievement_level', 'sekolah'),
            'achievement_pending' => $find('achievement_verification_status', 'menunggu'),
            'achievement_verified' => $find('achievement_verification_status', 'terverifikasi'),
            'correction_pending' => $find('correction_status', 'menunggu'),
        ];
    }
}
