<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ReportPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new InvalidArgumentException(
                'ReportPreviewSeeder hanya boleh dijalankan pada environment local atau testing.',
            );
        }

        DB::transaction(function (): void {
            $this->call([
                RoleSeeder::class,
                ReferenceSeeder::class,
                StudentSeeder::class,
            ]);

            $teacher = User::query()
                ->where('email', 'guru.bk@ruangbk.test')
                ->first();
            if ($teacher === null) {
                $this->call(AccountSeeder::class);
                $teacher = User::query()
                    ->where('email', 'guru.bk@ruangbk.test')
                    ->firstOrFail();
            }

            $year = AcademicYear::query()
                ->where('dapodik_id', 'SEED-ACADEMIC-YEAR')
                ->firstOrFail();
            $classroom = Classroom::query()
                ->where('dapodik_id', 'SEED-CLASS-X-RPL-1')
                ->firstOrFail();
            $students = Student::query()
                ->whereIn('nisn', ['0091234501', '0091234502', '0091234503'])
                ->get()
                ->keyBy('nisn');

            $this->assignTeacher($teacher, $classroom, $year);

            $fields = ReferenceValue::query()
                ->active()
                ->forCategory('service_field')
                ->pluck('id', 'code');
            $source = ReferenceValue::query()
                ->active()
                ->forCategory('case_source')
                ->where('code', 'murid_datang_sendiri')
                ->value('id');
            $status = ReferenceValue::query()
                ->active()
                ->forCategory('case_status')
                ->where('code', ServiceRecordStatus::NEEDS_FOLLOW_UP)
                ->value('id');
            $followUp = ReferenceValue::query()
                ->active()
                ->forCategory('follow_up_type')
                ->where('code', 'home_visit')
                ->value('id');
            $firstDate = $year->starts_on->addDays(75);

            $case = BkCase::query()
                ->withTrashed()
                ->firstOrNew(['registration_number' => 'K-DEMO-LAPORAN-001']);
            $case->forceFill([
                'student_id' => $students['0091234501']->getKey(),
                'temporary_student_id' => null,
                'academic_year_id' => $year->getKey(),
                'classroom_id' => $classroom->getKey(),
                'case_source_id' => $source,
                'service_field_id' => $fields->get('pribadi'),
                'status_id' => $status,
                'follow_up_type_id' => $followUp,
                'service_date' => $firstDate,
                'referrer' => null,
                'initial_info' => 'Murid mengalami kecemasan saat menyampaikan pendapat di depan kelas dan sering memilih diam meskipun telah memahami materi yang dibahas.',
                'initial_action' => 'Guru BK melakukan konseling individual, latihan pernapasan terarah, dan simulasi berbicara singkat secara bertahap di lingkungan yang aman.',
                'internal_note' => null,
                'resolution_summary' => 'Murid mulai mampu mengelola kecemasan, tetapi pemantauan keluarga masih diperlukan.',
                'closed_at' => null,
                'created_by' => $teacher->getKey(),
                'deleted_at' => null,
            ])->save();

            $owner = CaseAssignment::query()
                ->firstOrNew([
                    'case_id' => $case->getKey(),
                ]);
            $owner->forceFill([
                'user_id' => $teacher->getKey(),
                'reason' => 'Penanggung jawab kasus contoh laporan.',
                'assigned_by' => $teacher->getKey(),
            ])->save();

            $this->consultation(
                $students['0091234502']->getKey(),
                $teacher,
                (int) $fields->get('belajar'),
                $firstDate->addDay(),
                'Murid kesulitan membagi waktu antara tugas sekolah dan kegiatan organisasi sehingga beberapa tugas terlambat diselesaikan dalam dua minggu terakhir.',
                'Guru BK membantu menyusun prioritas, jadwal belajar mingguan, serta batas waktu realistis yang dapat dipantau bersama setiap akhir pekan.',
                'Murid menyepakati jadwal baru dan mampu menentukan tugas yang harus didahulukan.',
            );
            $this->consultation(
                $students['0091234503']->getKey(),
                $teacher,
                (int) $fields->get('sosial'),
                $firstDate->addDays(2),
                'Murid mengalami kesalahpahaman dengan anggota kelompok belajar yang menyebabkan komunikasi terhenti dan pembagian tugas tidak berjalan dengan baik.',
                'Guru BK memfasilitasi komunikasi asertif, klarifikasi peran, dan penyusunan kesepakatan kelompok agar setiap anggota memahami tanggung jawabnya.',
                'Murid dan kelompoknya telah berdamai serta membagi tugas secara jelas.',
            );
        });

        $this->command?->info('Berhasil membuat 1 kasus dan 2 konsultasi contoh laporan.');
    }

    private function assignTeacher(
        User $teacher,
        Classroom $classroom,
        AcademicYear $year,
    ): void {
        $assignment = TeacherAssignment::query()
            ->firstOrNew([
                'classroom_id' => $classroom->getKey(),
                'academic_year_id' => $year->getKey(),
            ]);
        $assignment->forceFill([
            'user_id' => $teacher->getKey(),
            'assigned_by' => $teacher->getKey(),
        ])->save();
    }

    private function consultation(
        ?int $studentId,
        User $teacher,
        int $serviceFieldId,
        CarbonInterface $date,
        string $problem,
        string $handling,
        string $result,
    ): void {
        $membership = StudentClassMembership::query()
            ->where('student_id', $studentId)
            ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
            ->first();
        $consultation = Consultation::query()
            ->withTrashed()
            ->where('student_id', $studentId)
            ->where('counselor_id', $teacher->getKey())
            ->whereDate('session_date', $date)
            ->first() ?? new Consultation;
        $consultation->forceFill([
            'student_id' => $studentId,
            'temporary_student_id' => null,
            'academic_year_id' => $membership?->academic_year_id,
            'classroom_id' => $membership?->classroom_id,
            'service_field_id' => $serviceFieldId,
            'session_date' => $date,
            'problem' => $problem,
            'handling' => $handling,
            'result' => $result,
            'counselor_id' => $teacher->getKey(),
            'deleted_at' => null,
        ])->save();
    }
}
