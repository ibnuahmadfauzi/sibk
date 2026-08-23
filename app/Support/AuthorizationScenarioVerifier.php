<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class AuthorizationScenarioVerifier
{
    /** @return list<string> */
    public function verify(?string $csvPath = null): array
    {
        $errors = [];
        $baseline = AuthorizationScenarioCatalog::baselineDate();
        if ($baseline === null) {
            $errors[] = 'Tanggal baseline tidak ditemukan dari RBAC-SK-AKTIF-MULTI.';

            return $errors;
        }

        $this->verifyActors($errors);
        $this->verifyAssignmentsAndMemberships($errors, $baseline->toDateString());
        $this->verifyCasesAndChildren($errors);
        $this->verifyConsultationsAndAchievements($errors);
        $this->verifyResolvedResources($errors);

        if ($csvPath !== null) {
            $this->verifyCsv($errors, $csvPath);
        }

        return $errors;
    }

    /** @param list<string> $errors */
    private function verifyActors(array &$errors): void
    {
        foreach (AuthorizationScenarioCatalog::actors() as $definition) {
            $user = DB::table('users')->where('email', $definition['email'])->first();
            if ($user === null) {
                $errors[] = 'Aktor tidak ditemukan: '.$definition['email'].'.';

                continue;
            }

            if ((bool) $user->is_active !== $definition['active']) {
                $errors[] = 'Status aktif tidak sesuai: '.$definition['email'].'.';
            }

            $roles = DB::table('roles')->join('user_roles', 'roles.id', '=', 'user_roles.role_id')
                ->where('user_roles.user_id', $user->id)->orderBy('roles.slug')->pluck('roles.slug')->all();
            $expected = $definition['roles'];
            sort($expected);
            if ($roles !== $expected) {
                $errors[] = 'Role tidak sesuai: '.$definition['email'].'.';
            }
        }
    }

    /** @param list<string> $errors */
    private function verifyAssignmentsAndMemberships(array &$errors, string $baseline): void
    {
        $expectedAssignments = [
            'RBAC-SK-AKTIF-A' => ['rbac.guru.a@ruangbk.test', 'RBAC XII-A', -180, null],
            'RBAC-SK-AKTIF-B' => ['rbac.guru.b@ruangbk.test', 'RBAC XII-B', -180, 89],
            'RBAC-SK-MASA-DEPAN' => ['rbac.guru.a@ruangbk.test', 'RBAC XII-B', 90, 180],
            'RBAC-SK-KEDALUWARSA' => ['rbac.guru.a@ruangbk.test', 'RBAC XII-M', -180, -1],
            'RBAC-SK-AKTIF-MULTI' => ['rbac.multi@ruangbk.test', 'RBAC XII-M', 0, null],
        ];

        foreach ($expectedAssignments as $decision => [$email, $classroom, $fromOffset, $untilOffset]) {
            $assignment = DB::table('teacher_assignments')
                ->join('users', 'users.id', '=', 'teacher_assignments.user_id')
                ->join('classrooms', 'classrooms.id', '=', 'teacher_assignments.classroom_id')
                ->where('teacher_assignments.decision_number', $decision)
                ->select('users.email', 'classrooms.name', 'teacher_assignments.effective_from', 'teacher_assignments.effective_until')
                ->first();
            $expectedFrom = date('Y-m-d', strtotime($baseline.' '.($fromOffset >= 0 ? '+' : '').$fromOffset.' days'));
            $expectedUntil = $untilOffset === null ? null : date('Y-m-d', strtotime($baseline.' '.($untilOffset >= 0 ? '+' : '').$untilOffset.' days'));
            if ($assignment === null
                || $assignment->email !== $email
                || $assignment->name !== $classroom
                || substr((string) $assignment->effective_from, 0, 10) !== $expectedFrom
                || ($assignment->effective_until === null ? null : substr((string) $assignment->effective_until, 0, 10)) !== $expectedUntil) {
                $errors[] = 'Penugasan kelas tidak sesuai baseline: '.$decision.'.';
            }
        }

        $expectedMemberships = [
            'RBAC-STUDENT-A' => [['RBAC XII-A', -180, null]],
            'RBAC-STUDENT-B' => [['RBAC XII-B', -180, null]],
            'RBAC-STUDENT-FUTURE' => [['RBAC XII-B', -180, null]],
            'RBAC-STUDENT-MULTI' => [['RBAC XII-A', -180, -1], ['RBAC XII-M', 0, null]],
        ];
        foreach ($expectedMemberships as $dapodikId => $periods) {
            $actual = DB::table('student_class_memberships')
                ->join('students', 'students.id', '=', 'student_class_memberships.student_id')
                ->join('classrooms', 'classrooms.id', '=', 'student_class_memberships.classroom_id')
                ->where('students.dapodik_id', $dapodikId)
                ->orderBy('student_class_memberships.effective_from')
                ->get(['classrooms.name', 'student_class_memberships.effective_from', 'student_class_memberships.effective_until']);
            if ($actual->count() !== count($periods)) {
                $errors[] = 'Jumlah histori kelas tidak sesuai: '.$dapodikId.'.';

                continue;
            }
            foreach ($periods as $index => [$classroom, $fromOffset, $untilOffset]) {
                $membership = $actual[$index];
                $expectedFrom = date('Y-m-d', strtotime($baseline.' '.($fromOffset >= 0 ? '+' : '').$fromOffset.' days'));
                $expectedUntil = $untilOffset === null ? null : date('Y-m-d', strtotime($baseline.' '.($untilOffset >= 0 ? '+' : '').$untilOffset.' days'));
                if ($membership->name !== $classroom
                    || substr((string) $membership->effective_from, 0, 10) !== $expectedFrom
                    || ($membership->effective_until === null ? null : substr((string) $membership->effective_until, 0, 10)) !== $expectedUntil) {
                    $errors[] = 'Histori kelas tidak sesuai: '.$dapodikId.'.';
                }
            }
        }
    }

    /** @param list<string> $errors */
    private function verifyCasesAndChildren(array &$errors): void
    {
        $expectedCases = [
            'K-RBAC-001' => ['RBAC-STUDENT-A', 'rbac.guru.a@ruangbk.test'],
            'K-RBAC-002' => ['RBAC-STUDENT-B', 'rbac.guru.b@ruangbk.test'],
            'K-RBAC-003' => ['RBAC-STUDENT-B', 'rbac.guru.b@ruangbk.test'],
            'K-RBAC-004' => ['RBAC-STUDENT-A', 'rbac.guru.a@ruangbk.test'],
            'K-RBAC-005' => ['RBAC-STUDENT-FUTURE', 'rbac.guru.b@ruangbk.test'],
        ];
        foreach ($expectedCases as $number => [$student, $creator]) {
            $case = DB::table('cases')->join('students', 'students.id', '=', 'cases.student_id')
                ->join('users', 'users.id', '=', 'cases.created_by')
                ->where('cases.registration_number', $number)
                ->first(['students.dapodik_id', 'users.email']);
            if ($case === null || $case->dapodik_id !== $student || $case->email !== $creator) {
                $errors[] = 'Identitas atau pemilik kasus tidak sesuai: '.$number.'.';
            }
        }

        $specialTeachers = DB::table('case_assignments')->join('cases', 'cases.id', '=', 'case_assignments.case_id')
            ->join('users', 'users.id', '=', 'case_assignments.user_id')
            ->where('cases.registration_number', 'K-RBAC-003')->orderBy('users.email')->pluck('users.email')->all();
        if ($specialTeachers !== ['rbac.guru.a@ruangbk.test', 'rbac.guru.b@ruangbk.test']) {
            $errors[] = 'Penugasan khusus K-RBAC-003 tidak sesuai.';
        }

        $waka = DB::table('case_coordinations')->join('cases', 'cases.id', '=', 'case_coordinations.case_id')
            ->join('users', 'users.id', '=', 'case_coordinations.waka_user_id')
            ->where('cases.registration_number', 'K-RBAC-002')->value('users.email');
        if ($waka !== 'rbac.waka.a@ruangbk.test') {
            $errors[] = 'Koordinasi K-RBAC-002 tidak ditujukan tepat kepada Waka A.';
        }

        $followUpCase = DB::table('follow_ups')->join('cases', 'cases.id', '=', 'follow_ups.case_id')
            ->where('cases.registration_number', 'K-RBAC-002')->value('cases.registration_number');
        if ($followUpCase !== 'K-RBAC-002') {
            $errors[] = 'Resource anak untuk skenario nested tidak ditemukan pada K-RBAC-002.';
        }
    }

    /** @param list<string> $errors */
    private function verifyConsultationsAndAchievements(array &$errors): void
    {
        $expectedConsultations = [
            'KNS-RBAC-001' => ['RBAC-STUDENT-A', 'rbac.guru.a@ruangbk.test', 'RBAC-PRIVATE-A'],
            'KNS-RBAC-002' => ['RBAC-STUDENT-B', 'rbac.guru.b@ruangbk.test', 'RBAC-PRIVATE-B'],
            'KNS-RBAC-003' => ['RBAC-STUDENT-MULTI', 'rbac.guru.a@ruangbk.test', 'RBAC-PRIVATE-HISTORY'],
        ];
        foreach ($expectedConsultations as $number => [$student, $counselor, $marker]) {
            $consultation = DB::table('consultations')->join('students', 'students.id', '=', 'consultations.student_id')
                ->join('users', 'users.id', '=', 'consultations.counselor_id')
                ->join('consultation_private_notes', 'consultation_private_notes.consultation_id', '=', 'consultations.id')
                ->where('consultations.registration_number', $number)
                ->first(['students.dapodik_id', 'users.email', 'consultation_private_notes.internal_note']);
            if ($consultation === null
                || $consultation->dapodik_id !== $student
                || $consultation->email !== $counselor
                || ! str_contains((string) $consultation->internal_note, $marker)) {
                $errors[] = 'Konsultasi atau marker privat tidak sesuai: '.$number.'.';
            }
        }

        $expectedAchievements = [
            'RBAC-Prestasi-Terverifikasi' => 'terverifikasi',
            'RBAC-Prestasi-Menunggu' => 'menunggu',
        ];
        foreach ($expectedAchievements as $activity => $status) {
            $actual = DB::table('achievements')->join('references', 'references.id', '=', 'achievements.verification_status_id')
                ->where('achievements.activity_name', $activity)->value('references.code');
            if ($actual !== $status) {
                $errors[] = 'Status prestasi tidak sesuai: '.$activity.'.';
            }
        }

        $notifications = DB::table('user_notifications')->join('users', 'users.id', '=', 'user_notifications.user_id')
            ->whereIn('deduplication_key', ['RBAC-NOTIFICATION-A', 'RBAC-NOTIFICATION-B'])
            ->pluck('users.email', 'deduplication_key')->all();
        if (($notifications['RBAC-NOTIFICATION-A'] ?? null) !== 'rbac.guru.a@ruangbk.test'
            || ($notifications['RBAC-NOTIFICATION-B'] ?? null) !== 'rbac.guru.b@ruangbk.test') {
            $errors[] = 'Kepemilikan notifikasi penelitian tidak sesuai.';
        }
    }

    /** @param list<string> $errors */
    private function verifyResolvedResources(array &$errors): void
    {
        $resources = AuthorizationScenarioCatalog::resourceIds();
        foreach (AuthorizationScenarioCatalog::scenarios() as $scenario) {
            foreach ($scenario['parameters'] ?? [] as $resourceKey) {
                if (! isset($resources[$resourceKey])) {
                    $errors[] = $scenario['id'].' tidak dapat me-resolve resource '.$resourceKey.'.';
                }
            }
        }
    }

    /** @param list<string> $errors */
    private function verifyCsv(array &$errors, string $path): void
    {
        if (! is_file($path)) {
            $errors[] = 'CSV hasil tidak ditemukan: '.$path.'.';

            return;
        }

        $actual = $this->readCsv($path);
        $expected = AuthorizationScenarioCatalog::resolvedRows();
        if (count($actual) !== count($expected)) {
            $errors[] = 'Jumlah baris CSV tidak sama dengan katalog.';

            return;
        }

        $checkedColumns = ['Dataset Version', 'Baseline Date', 'Scenario ID', 'Requirement ID', 'Actor', 'Resource Key', 'Resource Label', 'Preconditions', 'Request/Route', 'Expected Access', 'Expected Result', 'Must Appear', 'Must Not Appear'];
        foreach ($expected as $index => $row) {
            foreach ($checkedColumns as $column) {
                if (($actual[$index][$column] ?? null) !== $row[$column]) {
                    $errors[] = $row['Scenario ID'].' tidak selaras pada kolom '.$column.'.';
                }
            }
        }
    }

    /** @return list<array<string, string>> */
    private function readCsv(string $path): array
    {
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            return [];
        }
        $header = fgetcsv($stream, escape: '');
        if ($header === false) {
            fclose($stream);

            return [];
        }
        $header[0] = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0], '"');
        $rows = [];
        while (($values = fgetcsv($stream, escape: '')) !== false) {
            if (count($values) === count($header)) {
                $rows[] = array_combine($header, $values);
            }
        }
        fclose($stream);

        return $rows;
    }
}
