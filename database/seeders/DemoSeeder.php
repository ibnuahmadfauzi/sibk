<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (config('sibk.demo.enabled') !== true) {
            throw new InvalidArgumentException('Seeder demo memerlukan SIBK_DEMO_ENABLED=true.');
        }

        $password = (string) config('sibk.demo.account_password');
        if (mb_strlen($password) < 12 || preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/[0-9]/', $password) !== 1) {
            throw new InvalidArgumentException('SIBK_DEMO_ACCOUNT_PASSWORD harus berisi minimal 12 karakter, huruf, dan angka.');
        }

        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $startYear = $today->month >= 7 ? $today->year : $today->year - 1;
        $yearName = $startYear.'/'.($startYear + 1);
        $accounts = [
            'admin' => ['Admin Demo', 'admin@demo.ruangbk.test', 'admin_it'],
            'guru' => ['Guru BK Demo', 'guru@demo.ruangbk.test', 'guru_bk'],
            'koordinator' => ['Koordinator BK Demo', 'koordinator@demo.ruangbk.test', 'koordinator_bk'],
            'waka' => ['Waka Kesiswaan Demo', 'waka@demo.ruangbk.test', 'waka_kesiswaan'],
        ];
        $studentNumbers = ['9999900001', '9999900002', '9999900003'];

        DB::transaction(function () use ($password, $today, $startYear, $yearName, $accounts, $studentNumbers): void {
            if (AcademicYear::query()->where('preparation_reference', 'DEMO')->exists()) {
                return;
            }

            if (User::query()->whereNotIn('email', array_column($accounts, 1))->exists()
                || Student::query()->whereNotIn('nisn', $studentNumbers)->exists()
                || AcademicYear::query()->where(function ($query): void {
                    $query->whereNull('preparation_reference')->orWhere('preparation_reference', '!=', 'DEMO');
                })->exists()) {
                throw new InvalidArgumentException('Seeder demo hanya boleh dipakai pada database kosong atau database yang berisi data demo ini.');
            }

            $this->call([RoleSeeder::class, ReferenceSeeder::class]);

            $users = [];
            foreach ($accounts as $key => [$name, $email, $roleSlug]) {
                $users[$key] = User::query()->firstOrCreate(['email' => $email], [
                    'name' => $name,
                    'password' => $password,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
                $users[$key]->roles()->syncWithoutDetaching([Role::query()->where('slug', $roleSlug)->firstOrFail()->id]);
            }

            $year = AcademicYear::query()->firstOrCreate(['name' => $yearName], [
                'starts_on' => $startYear.'-07-01',
                'ends_on' => ($startYear + 1).'-06-30',
                'is_active' => true,
                'master_source' => AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                'source_confirmed_at' => now(),
                'prepared_by' => $users['admin']->id,
                'preparation_reference' => 'DEMO',
                'activated_by' => $users['admin']->id,
                'activated_at' => now(),
            ]);
            $classroom = Classroom::query()->firstOrCreate([
                'academic_year_id' => $year->id,
                'name' => 'X RPL Demo',
            ], [
                'grade_level' => 10,
                'major' => 'Rekayasa Perangkat Lunak',
                'is_active' => true,
                'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                'source_confirmed_at' => now(),
            ]);
            TeacherAssignment::query()->firstOrCreate([
                'classroom_id' => $classroom->id,
                'academic_year_id' => $year->id,
            ], [
                'user_id' => $users['guru']->id,
                'assigned_by' => $users['koordinator']->id,
            ]);

            $students = [];
            foreach (['Alya', 'Bima', 'Citra'] as $index => $name) {
                $students[] = Student::query()->firstOrCreate([
                    'nisn' => $studentNumbers[$index],
                ], [
                    'name' => 'Murid Demo '.$name,
                    'is_active' => true,
                    'master_source' => Student::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                    'source_confirmed_at' => now(),
                ]);
            }
            foreach ($students as $student) {
                StudentClassMembership::query()->firstOrCreate([
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                ], [
                    'classroom_id' => $classroom->id,
                    'is_active' => true,
                    'master_source' => StudentClassMembership::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                    'source_confirmed_at' => now(),
                ]);
            }

            $reference = static fn (string $category, string $code): int => ReferenceValue::query()
                ->where('category', $category)->where('code', $code)->firstOrFail()->id;
            $serviceDate = $today->subDays(2)->max(CarbonImmutable::create($startYear, 7, 1))->toDateString();
            foreach ([
                [$students[0], 'belajar', ServiceRecordStatus::IN_PROGRESS, 'Pendampingan rencana belajar.'],
                [$students[1], 'sosial', ServiceRecordStatus::NEEDS_FOLLOW_UP, 'Diskusi kerja sama kelompok.'],
            ] as [$student, $field, $status, $summary]) {
                $case = BkCase::query()->firstOrCreate([
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'service_field_id' => $reference('service_field', $field),
                ], [
                    'classroom_id' => $classroom->id,
                    'case_source_id' => $reference('case_source', 'temuan_guru_bk'),
                    'status_id' => $reference('case_status', $status),
                    'service_date' => $serviceDate,
                    'initial_info' => $summary,
                    'initial_action' => 'Guru BK menyusun langkah pendampingan.',
                    'created_by' => $users['guru']->id,
                ]);
                CaseAssignment::query()->firstOrCreate(['case_id' => $case->id], [
                    'user_id' => $users['guru']->id,
                    'reason' => 'Penugasan contoh demo.',
                    'assigned_by' => $users['koordinator']->id,
                ]);
            }

            Consultation::query()->firstOrCreate([
                'student_id' => $students[2]->id,
                'academic_year_id' => $year->id,
                'service_field_id' => $reference('service_field', 'karier'),
            ], [
                'classroom_id' => $classroom->id,
                'counselor_id' => $users['guru']->id,
                'session_date' => $serviceDate,
                'problem' => 'Murid ingin mengenal pilihan bidang kerja.',
                'handling' => 'Guru BK membahas minat dan pilihan belajar.',
                'result' => 'Murid menyusun daftar bidang yang ingin dieksplorasi.',
            ]);
        });
    }
}
