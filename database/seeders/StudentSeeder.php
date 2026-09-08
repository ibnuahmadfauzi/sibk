<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentClassMembership;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Menyediakan 20 data murid contoh untuk keperluan pengembangan dan demo.
 * Hanya boleh dijalankan pada environment local atau testing.
 */
class StudentSeeder extends Seeder
{
    private const PREFIX_DAPODIK = 'SEED-STUDENT-';
    private const PREFIX_YEAR    = 'SEED-ACADEMIC-YEAR';
    private const PREFIX_CLASS   = 'SEED-CLASS-';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new InvalidArgumentException('StudentSeeder hanya boleh dijalankan pada environment local atau testing.');
        }

        DB::transaction(function (): void {
            $this->removeExisting();
            $this->seedStudents();
        });
    }

    private function removeExisting(): void
    {
        $studentIds = DB::table('students')
            ->where('dapodik_id', 'like', self::PREFIX_DAPODIK.'%')
            ->pluck('id');

        // Hapus membership lewat student_id karena tabel tidak memiliki dapodik_id
        DB::table('student_class_memberships')
            ->whereIn('student_id', $studentIds)
            ->delete();

        DB::table('students')->whereIn('id', $studentIds)->delete();

        DB::table('classrooms')
            ->where('dapodik_id', 'like', self::PREFIX_CLASS.'%')
            ->delete();

        DB::table('academic_years')
            ->where('dapodik_id', self::PREFIX_YEAR)
            ->delete();
    }

    private function seedStudents(): void
    {
        $today = CarbonImmutable::today();
        $start = $today->startOfYear();
        $end   = $today->endOfYear();

        // ----------------------------------------------------------------
        // Tahun Ajaran
        // ----------------------------------------------------------------
        $year = AcademicYear::query()->create([
            'dapodik_id' => self::PREFIX_YEAR,
            'name'       => 'Demo '.$start->format('Y').'/'.$end->format('Y'),
            'starts_on'  => $start,
            'ends_on'    => $end,
            'is_active'  => true,
            'synced_at'  => now(),
        ]);

        // ----------------------------------------------------------------
        // Kelas (4 kelas: X-RPL-1, XI-RPL-1, XII-RPL-1, XII-TKJ-1)
        // ----------------------------------------------------------------
        $classes = [
            'x_rpl_1'   => $this->classroom($year, 'X-RPL-1', 10, 'Rekayasa Perangkat Lunak'),
            'xi_rpl_1'  => $this->classroom($year, 'XI-RPL-1', 11, 'Rekayasa Perangkat Lunak'),
            'xii_rpl_1' => $this->classroom($year, 'XII-RPL-1', 12, 'Rekayasa Perangkat Lunak'),
            'xii_tkj_1' => $this->classroom($year, 'XII-TKJ-1', 12, 'Teknik Komputer dan Jaringan'),
            'x_tkj_1'   => $this->classroom($year, 'X-TKJ-1', 10, 'Teknik Komputer dan Jaringan'),
            'xi_tkj_1'  => $this->classroom($year, 'XI-TKJ-1', 11, 'Teknik Komputer dan Jaringan'),
        ];

        // ----------------------------------------------------------------
        // 20 Data Murid
        // ----------------------------------------------------------------
        $muridData = [
            // Kelas X-RPL-1 (5 murid)
            ['01', '0091234501', 'Aisyah Rahmawati',    'x_rpl_1'],
            ['02', '0091234502', 'Bintang Pratama',      'x_rpl_1'],
            ['03', '0091234503', 'Citra Dewi Lestari',  'x_rpl_1'],
            ['04', '0091234504', 'Dimas Aryo Saputro',  'x_rpl_1'],
            ['05', '0091234505', 'Elsa Nadia Putri',    'x_rpl_1'],
            // Kelas XI-RPL-1 (5 murid)
            ['06', '0091234506', 'Fajar Hidayat',       'xi_rpl_1'],
            ['07', '0091234507', 'Gita Permatasari',    'xi_rpl_1'],
            ['08', '0091234508', 'Hendra Setiawan',     'xi_rpl_1'],
            ['09', '0091234509', 'Indah Kurniawati',    'xi_rpl_1'],
            ['10', '0091234510', 'Joko Santoso',        'xi_rpl_1'],
            // Kelas XII-RPL-1 (5 murid)
            ['11', '0091234511', 'Kharisma Agung',      'xii_rpl_1'],
            ['12', '0091234512', 'Laila Nurfadillah',   'xii_rpl_1'],
            ['13', '0091234513', 'Muhamad Rizki',       'xii_rpl_1'],
            ['14', '0091234514', 'Nanda Aulia Sari',    'xii_rpl_1'],
            ['15', '0091234515', 'Oscar Putra Wijaya',  'xii_rpl_1'],
            // Kelas XII-TKJ-1 (5 murid)
            ['16', '0091234516', 'Putri Maharani',      'xii_tkj_1'],
            ['17', '0091234517', 'Rafi Ahmad Fauzi',    'xii_tkj_1'],
            ['18', '0091234518', 'Sari Wulandari',      'xii_tkj_1'],
            ['19', '0091234519', 'Tegar Bagas Nugroho', 'xii_tkj_1'],
            ['20', '0091234520', 'Ulfah Nur Azizah',    'xii_tkj_1'],
            // Kelas X-TKJ-1 (8 murid)
            ['21', '0091234521', 'Vito Erlangga',       'x_tkj_1'],
            ['22', '0091234522', 'Winda Ayu Safitri',   'x_tkj_1'],
            ['23', '0091234523', 'Xander Prabowo',      'x_tkj_1'],
            ['24', '0091234524', 'Yasmin Khalida',      'x_tkj_1'],
            ['25', '0091234525', 'Zaki Maulana',        'x_tkj_1'],
            ['26', '0091234526', 'Anggi Setiabudi',     'x_tkj_1'],
            ['27', '0091234527', 'Bayu Nugraha',        'x_tkj_1'],
            ['28', '0091234528', 'Cantika Puspita',     'x_tkj_1'],
            // Kelas XI-TKJ-1 (7 murid)
            ['29', '0091234529', 'Deni Firmansyah',     'xi_tkj_1'],
            ['30', '0091234530', 'Eka Putri Rahayu',    'xi_tkj_1'],
            ['31', '0091234531', 'Fauzan Ramadhan',     'xi_tkj_1'],
            ['32', '0091234532', 'Gilang Saputra',      'xi_tkj_1'],
            ['33', '0091234533', 'Hani Kusumawati',     'xi_tkj_1'],
            ['34', '0091234534', 'Irwan Syahputra',     'xi_tkj_1'],
            ['35', '0091234535', 'Julia Anggraini',     'xi_tkj_1'],
        ];

        foreach ($muridData as [$no, $nisn, $nama, $classKey]) {
            $student = Student::query()->create([
                'dapodik_id' => self::PREFIX_DAPODIK.$no,
                'nisn'       => $nisn,
                'name'       => $nama,
                'is_active'  => true,
                'synced_at'  => now(),
            ]);

            StudentClassMembership::query()->create([
                'student_id'       => $student->id,
                'classroom_id'     => $classes[$classKey]->id,
                'academic_year_id' => $year->id,
                'effective_from'   => $start,
                'effective_until'  => null,
            ]);
        }
    }

    private function classroom(
        AcademicYear $year,
        string $suffix,
        int $gradeLevel,
        string $major,
    ): Classroom {
        return Classroom::query()->create([
            'dapodik_id'       => self::PREFIX_CLASS.$suffix,
            'academic_year_id' => $year->id,
            'name'             => $suffix,
            'grade_level'      => $gradeLevel,
            'major'            => $major,
            'is_active'        => true,
            'synced_at'        => now(),
        ]);
    }
}
