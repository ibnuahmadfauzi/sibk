<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QaDataResetter extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new InvalidArgumentException('Reset data QA hanya boleh dijalankan pada environment local atau testing.');
        }

        self::reset();
    }

    public static function reset(): void
    {
        DB::transaction(function (): void {
            $qaUserEmails = [
                'guru.bk.qa.a@ruangbk.test',
                'guru.bk.qa.b@ruangbk.test',
                'guru.bk.qa.c@ruangbk.test',
                'koordinator.bk.qa@ruangbk.test',
                'waka.kesiswaan.qa@ruangbk.test',
                'admin.it.qa@ruangbk.test',
                'guru.bk.qa.nonaktif@ruangbk.test',
            ];

            $userIds = DB::table('users')
                ->whereIn('email', $qaUserEmails)
                ->pluck('id');

            $studentIds = DB::table('students')
                ->where('dapodik_id', 'like', 'QA-STU-%')
                ->orWhere('nisn', 'like', '9926%')
                ->pluck('id');

            $classroomIds = DB::table('classrooms')
                ->where('dapodik_id', 'like', 'QA-CLS-%')
                ->pluck('id');

            $academicYearIds = DB::table('academic_years')
                ->where('dapodik_id', 'like', 'QA-AY-%')
                ->pluck('id');

            $caseIds = DB::table('cases')
                ->where('registration_number', 'like', 'K-QA-%')
                ->orWhereIn('student_id', $studentIds)
                ->orWhereIn('created_by', $userIds)
                ->pluck('id');

            $consultationIds = DB::table('consultations')
                ->where('registration_number', 'like', 'KNS-QA-%')
                ->orWhereIn('student_id', $studentIds)
                ->orWhereIn('counselor_id', $userIds)
                ->pluck('id');

            $tatibIds = DB::table('external_tatib_records')
                ->where('source_identifier', 'like', 'QA-ETATIB-%')
                ->orWhere('nisn', 'like', '9926%')
                ->orWhereIn('student_id', $studentIds)
                ->pluck('id');

            $achievementIds = DB::table('achievements')
                ->where('evidence_reference', 'like', 'QA-EVD-%')
                ->orWhere('activity_name', 'like', 'QA %')
                ->orWhereIn('student_id', $studentIds)
                ->orWhereIn('recorded_by', $userIds)
                ->pluck('id');

            if ($userIds->isNotEmpty()) {
                DB::table('audit_logs')->whereIn('actor_id', $userIds)->delete();
                DB::table('user_notifications')->whereIn('user_id', $userIds)->delete();
                DB::table('corrections')->whereIn('requester_id', $userIds)->delete();
                DB::table('user_roles')->whereIn('user_id', $userIds)->delete();
            }

            if ($consultationIds->isNotEmpty()) {
                DB::table('consultation_private_notes')->whereIn('consultation_id', $consultationIds)->delete();
                DB::table('consultations')->whereIn('id', $consultationIds)->delete();
            }

            if ($caseIds->isNotEmpty() || $tatibIds->isNotEmpty()) {
                DB::table('case_etatib_links')
                    ->whereIn('case_id', $caseIds)
                    ->orWhereIn('external_tatib_record_id', $tatibIds)
                    ->delete();
            }

            if ($caseIds->isNotEmpty()) {
                DB::table('follow_ups')->whereIn('case_id', $caseIds)->delete();
                DB::table('case_coordinations')->whereIn('case_id', $caseIds)->delete();
                DB::table('case_assignments')->whereIn('case_id', $caseIds)->delete();
                DB::table('cases')->whereIn('id', $caseIds)->delete();
            }

            if ($achievementIds->isNotEmpty()) {
                DB::table('achievements')->whereIn('id', $achievementIds)->delete();
            }

            if ($tatibIds->isNotEmpty()) {
                DB::table('external_tatib_records')->whereIn('id', $tatibIds)->delete();
            }

            DB::table('teacher_assignments')
                ->where('decision_number', 'like', 'QA-SK-%')
                ->orWhereIn('user_id', $userIds)
                ->delete();

            DB::table('student_class_memberships')
                ->where('dapodik_id', 'like', 'QA-MEM-%')
                ->orWhereIn('student_id', $studentIds)
                ->orWhereIn('classroom_id', $classroomIds)
                ->delete();

            if ($studentIds->isNotEmpty()) {
                DB::table('students')->whereIn('id', $studentIds)->delete();
            }

            if ($classroomIds->isNotEmpty()) {
                DB::table('classrooms')->whereIn('id', $classroomIds)->delete();
            }

            if ($academicYearIds->isNotEmpty()) {
                DB::table('academic_years')->whereIn('id', $academicYearIds)->delete();
            }

            if ($userIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $userIds)->delete();
            }
        });
    }
}
