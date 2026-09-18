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
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Seeder untuk menyediakan 15 data dummy kasus dan 15 data dummy layanan konseling (BK).
 * Terbagi merata pada 4 bidang bimbingan: Pribadi, Belajar, Sosial, dan Karier.
 *
 * Jalankan dengan:
 * php artisan db:seed --class=Database\\Seeders\\DummyCaseAndServiceSeeder
 */
class DummyCaseAndServiceSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new InvalidArgumentException('DummyCaseAndServiceSeeder hanya boleh dijalankan pada environment local atau testing.');
        }

        DB::transaction(function (): void {
            // 1. Pastikan data dasar tersedia
            $this->call([
                RoleSeeder::class,
                ReferenceSeeder::class,
                AccountSeeder::class,
                StudentSeeder::class,
            ]);

            $guruBk = User::query()->where('email', 'guru.bk@ruangbk.test')->firstOrFail();
            $academicYear = AcademicYear::query()->active()->firstOrFail();

            // 2. Pastikan Guru BK memiliki penugasan kelas agar murid berada dalam scope aktifnya
            $this->ensureTeacherAssignments($guruBk, $academicYear);

            // 3. Cache ID Referensi
            $fields = ReferenceValue::query()->active()->forCategory('service_field')->pluck('id', 'code');
            $sources = ReferenceValue::query()->active()->forCategory('case_source')->pluck('id', 'code');
            $caseStatuses = ReferenceValue::query()->active()->forCategory('case_status')->pluck('id', 'code');
            $followUpTypes = ReferenceValue::query()->active()->forCategory('follow_up_type')->pluck('id', 'code');

            // 4. Buat 15 Kasus BK
            $this->seedCases($guruBk, $fields, $sources, $caseStatuses, $followUpTypes);

            // 5. Buat 15 Layanan Konseling (Konsultasi)
            $this->seedConsultations($guruBk, $fields);
        });

        $this->command?->info('Berhasil membuat 15 data kasus dan 15 data layanan konseling BK.');
    }

    private function ensureTeacherAssignments(User $guruBk, AcademicYear $academicYear): void
    {
        $classrooms = Classroom::query()->active()->get();
        foreach ($classrooms as $classroom) {
            TeacherAssignment::query()->updateOrCreate(
                [
                    'user_id' => $guruBk->id,
                    'classroom_id' => $classroom->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'effective_from' => $academicYear->starts_on?->toDateString() ?? '2026-07-01',
                    'effective_until' => null,
                    'decision_number' => 'SK-BK-DEMO-2026',
                    'notes' => 'Penugasan Guru BK pengampu demo',
                    'assigned_by' => $guruBk->id,
                ],
            );
        }
    }

    /**
     * @param  Collection<string, int>  $fields
     * @param  Collection<string, int>  $sources
     * @param  Collection<string, int>  $caseStatuses
     * @param  Collection<string, int>  $followUpTypes
     * @return array<int, BkCase>
     */
    private function seedCases(
        User $guruBk,
        $fields,
        $sources,
        $caseStatuses,
        $followUpTypes,
    ): array {
        $definitions = [
            // ==================== BIDANG PRIBADI (4) ====================
            [
                'index' => 1,
                'nisn' => '0091234501', // Aisyah Rahmawati (X-RPL-1)
                'field' => 'pribadi',
                'source' => 'murid_datang_sendiri',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'service_date' => '2026-09-08',
                'referrer' => null,
                'initial_info' => 'Murid datang sendiri ke ruang BK mengeluhkan kecemasan berlebih saat presentasi di depan kelas dan sering merasa sesak napas sebelum pelajaran produktif.',
                'initial_action' => 'Melakukan konseling individual awal untuk menstabilkan emosi, mendengarkan keluhan secara empati, serta mengajarkan teknik pernapasan relaksasi diafragma.',
                'internal_note' => 'Ada kecenderungan kecemasan performa akibat pengalaman perundungan verbal saat masih di jenjang SMP. Perlu penguatan desensitisasi bertahap.',
                'waka_summary' => null,
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => null,
                'closed_at' => null,
            ],
            [
                'index' => 2,
                'nisn' => '0091234502', // Bintang Pratama (X-RPL-1)
                'field' => 'pribadi',
                'source' => 'temuan_guru_bk',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'service_date' => '2026-09-15',
                'referrer' => null,
                'initial_info' => 'Guru BK mengamati murid sering menyendiri di sudut lorong saat jam istirahat, tatapan kosong, dan mengalami perubahan drastis pada kerapian seragam sekolah.',
                'initial_action' => 'Melakukan pendekatan informal saat jam pulang sekolah dan menjadwalkan sesi bimbingan personal empat mata.',
                'internal_note' => 'Terindikasi sedang mengalami fase menarik diri dari pergaulan sebaya. Perlu kehati-hatian dalam menggali permasalahan keluarga.',
                'waka_summary' => null,
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => null,
                'closed_at' => null,
            ],
            [
                'index' => 3,
                'nisn' => '0091234511', // Kharisma Agung (XII-RPL-1)
                'field' => 'pribadi',
                'source' => 'rujukan',
                'status' => ServiceRecordStatus::NEEDS_FOLLOW_UP,
                'follow_up_type' => 'home_visit',
                'service_date' => '2026-09-10',
                'referrer' => 'Wali Kelas XII-RPL-1',
                'initial_info' => 'Wali kelas melaporkan murid sering tidak pulang ke rumah selama 2 hari dan menginap di tempat rekan akibat pertengkaran hebat dengan orang tua di rumah.',
                'initial_action' => 'Mengamankan kondisi emosional murid di ruang BK, memberikan dukungan konseling personal, dan merencanakan mediasi bersama pihak keluarga.',
                'internal_note' => 'Konflik keluarga terkait perceraian orang tua yang berdampak pada stabilitas psikologis murid.',
                'waka_summary' => 'Murid mengalami krisis situasi keluarga; BK menjadwalkan pertemuan klarifikasi bersama wali murid.',
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => 'Home visit dan koordinasi lanjutan bersama wali murid.',
                'closed_at' => null,
            ],
            [
                'index' => 4,
                'nisn' => '0091234516', // Putri Maharani (XII-TKJ-1)
                'field' => 'pribadi',
                'source' => 'temuan_guru_bk',
                'status' => ServiceRecordStatus::COMPLETED,
                'service_date' => '2026-08-25',
                'referrer' => null,
                'initial_info' => 'Murid pingsan di ruang laboratorium jaringan karena serangan panik (panic attack) menjelang simulasi asesmen sertifikasi keahlian kejuruan.',
                'initial_action' => 'Pertolongan pertama di UKS dilanjutkan konseling restrukturisasi kognitif dan pembiasaan afirmasi positif.',
                'internal_note' => 'Murid memiliki tuntutan perfeksionisme yang sangat tinggi terhadap diri sendiri.',
                'waka_summary' => 'Penanganan kepanikan ujian kejuruan murid tuntas; murid kembali mengikuti simulasi dengan stabil.',
                'final_result' => 'Murid berhasil menguasai teknik grounding emosi dan sukses menyelesaikan asesmen sertifikasi tanpa kepanikan.',
                'resolution_summary' => 'Diberikan 3 kali sesi konseling bertahap dengan evaluasi skala kecemasan yang menurun stabil.',
                'continued_plan' => 'Pemantauan berkala oleh wali kelas saat ujian berlangsung.',
                'closed_at' => '2026-09-08',
            ],

            // ==================== BIDANG BELAJAR (4) ====================
            [
                'index' => 5,
                'nisn' => '0091234504', // Dimas Aryo Saputro (X-RPL-1)
                'field' => 'belajar',
                'source' => 'rujukan',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'service_date' => '2026-09-09',
                'referrer' => 'Guru Mapel Pemrograman Dasar',
                'initial_info' => 'Tertinggal 5 modul penugasan praktikum algoritma pemrograman. Murid menyatakan merasa salah jurusan dan tidak mampu memahami logika looping/array.',
                'initial_action' => 'Melakukan asesmen modalitas gaya belajar dan memfasilitasi komunikasi dengan guru mapel untuk penugasan adaptif.',
                'internal_note' => 'Gaya belajar murid dominan kinestetik dan visual, sedangkan materi pembelajaran sebelumnya disajikan secara abstrak tekstual.',
                'waka_summary' => null,
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => null,
                'closed_at' => null,
            ],
            [
                'index' => 6,
                'nisn' => '0091234508', // Hendra Setiawan (XI-RPL-1)
                'field' => 'belajar',
                'source' => 'temuan_guru_bk',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'service_date' => '2026-09-14',
                'referrer' => null,
                'initial_info' => 'Berdasarkan rekapitulasi penilaian tengah semester, nilai kejuruan murid mengalami penurunan tajam di bawah KKM pada 3 mata pelajaran konsentrasi RPL.',
                'initial_action' => 'Memanggil murid untuk wawancara diagnostik kesulitan belajar dan analisis alokasi waktu belajar di rumah.',
                'internal_note' => 'Murid terindikasi mengalami adiksi game online larut malam sehingga daya tangkap saat jam sekolah menurun.',
                'waka_summary' => null,
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => null,
                'closed_at' => null,
            ],
            [
                'index' => 7,
                'nisn' => '0091234506', // Fajar Hidayat (XI-RPL-1)
                'field' => 'belajar',
                'source' => 'e_tatib',
                'status' => ServiceRecordStatus::NEEDS_FOLLOW_UP,
                'follow_up_type' => 'surat_panggilan_orang_tua',
                'service_date' => '2026-09-11',
                'referrer' => null,
                'initial_info' => 'Tercatat dalam sistem e-Tatib sering tertidur di jam pelajaran bengkel serta lalai mengumpulkan laporan proyek sistem basis data.',
                'initial_action' => 'Klarifikasi penyebab rasa kantuk kronis di sekolah dan perumusan target komitmen belajar baru bersama wali kelas.',
                'internal_note' => 'Murid membantu menjaga usaha dagang orang tua hingga pukul 01.00 WIB setiap malam untuk membantu kebutuhan ekonomi keluarga.',
                'waka_summary' => 'Koordinasi dispensasi toleransi fisik dan restrukturisasi jadwal belajar mandiri murid.',
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => 'Pertemuan dengan orang tua untuk menyelaraskan jam istirahat murid di rumah.',
                'closed_at' => null,
            ],
            [
                'index' => 8,
                'nisn' => '0091234513', // Muhamad Rizki (XII-RPL-1)
                'field' => 'belajar',
                'source' => 'murid_datang_sendiri',
                'status' => ServiceRecordStatus::COMPLETED,
                'service_date' => '2026-08-20',
                'referrer' => null,
                'initial_info' => 'Murid merasa kewalahan dan buntu (burnout) saat mengerjakan proyek akhir tugas sekolah berbasis klien nyata.',
                'initial_action' => 'Pelatihan teknik time management Matrix Eisenhower dan dekonstruksi tugas besar menjadi target-target kecil harian.',
                'internal_note' => 'Murid memiliki hambatan kecemasan perfeksionisme; takut kode buatannya ditolak oleh pembimbing industri.',
                'waka_summary' => 'Bimbingan efektivitas penyelesaian proyek akhir SMK tuntas; murid mampu menyelesaikan milestone tepat waktu.',
                'final_result' => 'Murid berhasil merampungkan seluruh modul aplikasi dan dipresentasikan di depan penguji industri dengan predikat amat baik.',
                'resolution_summary' => 'Selesai melalui 4 sesi bimbingan terjadwal dan monitoring checklist mingguan.',
                'continued_plan' => 'Refleksi pencapaian dan penyiapan berkas pameran karya SMK.',
                'closed_at' => '2026-09-06',
            ],

            // ==================== BIDANG SOSIAL (4) ====================
            [
                'index' => 9,
                'nisn' => '0091234503', // Citra Dewi Lestari (X-RPL-1)
                'field' => 'sosial',
                'source' => 'rujukan',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'service_date' => '2026-09-11',
                'referrer' => 'Wali Kelas X-RPL-1',
                'initial_info' => 'Terjadi perselisihan di grup chat kelas yang mengakibatkan murid dikucilkan dan enggan masuk ke ruang kelas.',
                'initial_action' => 'Konseling individu untuk memvalidasi perasaan terluka murid serta memanggil pihak-pihak terkait untuk klarifikasi pesan.',
                'internal_note' => 'Adanya miskomunikasi intonasi tulisan pada media sosial; bukan perundungan terencana, namun memicu pengelompokan (geng).',
                'waka_summary' => null,
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => null,
                'closed_at' => null,
            ],
            [
                'index' => 10,
                'nisn' => '0091234507', // Gita Permatasari (XI-RPL-1)
                'field' => 'sosial',
                'source' => 'temuan_guru_bk',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'service_date' => '2026-09-15',
                'referrer' => null,
                'initial_info' => 'Murid menunjukkan sikap pasif-agresif dan menolak bergabung dalam kelompok praktikum lab kejuruan dengan alasan tidak cocok dengan teman satu bangku.',
                'initial_action' => 'Mendengarkan keberatan murid dan merancang latihan keterampilan komunikasi asertif.',
                'internal_note' => 'Murid merasa rekan kelompoknya menumpang nama (free rider) saat pengerjaan tugas proyek.',
                'waka_summary' => null,
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => null,
                'closed_at' => null,
            ],
            [
                'index' => 11,
                'nisn' => '0091234519', // Tegar Bagas Nugroho (XII-TKJ-1)
                'field' => 'sosial',
                'source' => 'e_tatib',
                'status' => ServiceRecordStatus::NEEDS_FOLLOW_UP,
                'follow_up_type' => 'surat_pernyataan',
                'service_date' => '2026-09-12',
                'referrer' => null,
                'initial_info' => 'Terlibat adu mulut dan saling dorong di kantin sekolah dengan murid dari jurusan lain akibat kesalahpahaman antrean.',
                'initial_action' => 'Pemisahan kedua belah pihak, pendinginan emosi (cooling down), serta mediasi damai bertatap muka di ruang BK.',
                'internal_note' => 'Perlu penanaman regulasi emosi amarah (anger management) agar tidak terpicu oleh provokasi verbal sesaat.',
                'waka_summary' => 'Insiden perselisihan kantin telah diredam; kedua murid membuat surat kesepakatan damai dengan pantauan ketertiban.',
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => 'Monitoring interaksi antarkelas oleh tim Satgas Tatib dan BK selama 2 pekan ke depan.',
                'closed_at' => null,
            ],
            [
                'index' => 12,
                'nisn' => '0091234521', // Vito Erlangga (X-TKJ-1)
                'field' => 'sosial',
                'source' => 'rujukan',
                'status' => ServiceRecordStatus::COMPLETED,
                'service_date' => '2026-08-28',
                'referrer' => 'Guru Pendidikan Jasmani',
                'initial_info' => 'Sering melontarkan ejekan fisik (body shaming) kepada rekan sekelas saat kegiatan olahraga di lapangan.',
                'initial_action' => 'Pemanggilan murid, refleksi dampak psikologis perundungan verbal bagi korban, dan pembinaan empati moral.',
                'internal_note' => 'Murid meniru pola bercanda di lingkungan bermain luar sekolah tanpa memahami batas kepatutan di institusi pendidikan.',
                'waka_summary' => 'Pembinaan perilaku perundungan verbal tuntas; murid meminta maaf langsung dan menunjukkan perbaikan sikap.',
                'final_result' => 'Murid membuat komitmen tertulis saling menghargai dan meminta maaf secara terbuka kepada murid yang bersangkutan.',
                'resolution_summary' => 'Proses mediasi restoratif berhasil menyadarkan murid dan tidak ditemukan pengulangan perilaku.',
                'continued_plan' => 'Evaluasi berkala oleh guru mata pelajaran dan wali kelas.',
                'closed_at' => '2026-09-05',
            ],

            // ==================== BIDANG KARIER (3) ====================
            [
                'index' => 13,
                'nisn' => '0091234510', // Joko Santoso (XI-RPL-1)
                'field' => 'karier',
                'source' => 'murid_datang_sendiri',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'service_date' => '2026-09-10',
                'referrer' => null,
                'initial_info' => 'Murid bimbang memilih lokasi Praktik Kerja Lapangan (PKL) antara instansi pemerintah daerah atau software agency swasta komersial.',
                'initial_action' => 'Melakukan pemetaan kompetensi teknis, penelusuran portofolio coding, dan pemberian gambaran budaya kerja pada masing-masing industri.',
                'internal_note' => 'Minat murid lebih condong ke backend development, namun ada kekhawatiran jarak tempuh transportasi dari rumah.',
                'waka_summary' => null,
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => null,
                'closed_at' => null,
            ],
            [
                'index' => 14,
                'nisn' => '0091234512', // Laila Nurfadillah (XII-RPL-1)
                'field' => 'karier',
                'source' => 'rujukan',
                'status' => ServiceRecordStatus::NEEDS_FOLLOW_UP,
                'follow_up_type' => 'surat_panggilan_orang_tua',
                'service_date' => '2026-09-14',
                'referrer' => 'Koordinator Bursa Kerja Khusus (BKK)',
                'initial_info' => 'Murid mengalami dilema arah karir: orang tua meminta langsung bekerja di pabrik garmen, sementara minat kuat murid ingin kuliah vokasi D4 Teknik Informatika.',
                'initial_action' => 'Eksplorasi potensi beasiswa KIP-Kuliah dan penyiapan bahan pertimbangan prospek masa depan untuk disampaikan kepada keluarga.',
                'internal_note' => 'Kendala utama adalah persepsi ekonomi orang tua yang menganggap kuliah selalu berbiaya mahal.',
                'waka_summary' => 'BK mendampingi advokasi peluang beasiswa perguruan tinggi vokasi negeri bagi murid berprestasi.',
                'final_result' => null,
                'resolution_summary' => null,
                'continued_plan' => 'Mengundang orang tua murid dalam forum konsultasi karir bersama BKK.',
                'closed_at' => null,
            ],
            [
                'index' => 15,
                'nisn' => '0091234517', // Rafi Ahmad Fauzi (XII-TKJ-1)
                'field' => 'karier',
                'source' => 'murid_datang_sendiri',
                'status' => ServiceRecordStatus::COMPLETED,
                'service_date' => '2026-08-22',
                'referrer' => null,
                'initial_info' => 'Murid merasa cemas dan kurang percaya diri menghadapi tahapan tes wawancara teknis rekrutmen magang industri telekomunikasi.',
                'initial_action' => 'Simulasi wawancara kerja (mock interview), kurasi portofolio sertifikasi MikroTik, dan latihan komunikasi personal.',
                'internal_note' => 'Potensi teknis murid sangat baik, hambatan ada pada artikulasi bicara saat berada di bawah tekanan wawancara.',
                'waka_summary' => 'Pendampingan kesiapan wawancara kerja industri berhasil; murid dinyatakan lolos seleksi magang telekomunikasi.',
                'final_result' => 'Murid lulus wawancara industri dan resmi diterima penempatan magang kerja pada PT Telkom Akses.',
                'resolution_summary' => 'Diberikan 2 kali sesi latihan simulasi wawancara dan review curriculum vitae secara intensif.',
                'continued_plan' => 'Monitoring berkala penyesuaian diri selama masa awal magang industri.',
                'closed_at' => '2026-09-02',
            ],
        ];

        $seededCases = [];

        foreach ($definitions as $def) {
            $student = Student::query()->where('nisn', $def['nisn'])->firstOrFail();
            $regNumber = sprintf('K-%s-%04d', substr($def['service_date'], 0, 4), $def['index']);

            /** @var BkCase $case */
            $case = BkCase::query()->updateOrCreate(
                ['registration_number' => $regNumber],
                [
                    'student_id' => $student->id,
                    'temporary_student_id' => null,
                    'case_source_id' => $sources[$def['source']],
                    'service_field_id' => $fields[$def['field']],
                    'status_id' => $caseStatuses[$def['status']],
                    'follow_up_type_id' => isset($def['follow_up_type']) ? $followUpTypes[$def['follow_up_type']] : null,
                    'service_date' => $def['service_date'],
                    'referrer' => $def['referrer'],
                    'initial_info' => $def['initial_info'],
                    'initial_action' => $def['initial_action'],
                    'internal_note' => $def['internal_note'],
                    'waka_summary' => $def['waka_summary'],
                    'final_result' => $def['final_result'],
                    'resolution_summary' => $def['resolution_summary'],
                    'continued_plan' => $def['continued_plan'],
                    'closed_at' => $def['closed_at'],
                    'created_by' => $guruBk->id,
                ],
            );

            // Buat penugasan owner untuk Guru BK
            CaseAssignment::query()->updateOrCreate(
                [
                    'case_id' => $case->id,
                    'user_id' => $guruBk->id,
                    'assignment_type' => CaseAssignment::TYPE_OWNER,
                ],
                [
                    'effective_from' => $def['service_date'],
                    'effective_until' => null,
                    'reason' => 'Penanggung jawab utama kasus murid.',
                    'assigned_by' => $guruBk->id,
                ],
            );

            $seededCases[$def['index']] = $case;
        }

        return $seededCases;
    }

    /**
     * @param  Collection<string, int>  $fields
     */
    private function seedConsultations(
        User $guruBk,
        $fields,
    ): void {
        $definitions = [
            // ==================== BIDANG PRIBADI (4) ====================
            [
                'index' => 1,
                'nisn' => '0091234501', // Aisyah Rahmawati
                'case_index' => 1,
                'field' => 'pribadi',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'session_date' => '2026-09-10',
                'starts_at' => '09:00',
                'ends_at' => '10:00',
                'follow_up_date' => '2026-09-17',
                'topic' => 'Regulasi emosi dan manajemen kecemasan berbicara di depan umum.',
                'general_summary' => 'Murid berlatih teknik relaksasi pernapasan dan membuat daftar situasi yang memicu kecemasan di kelas.',
                'internal_note' => 'Murid menunjukkan respons positif terhadap teknik visualisasi diri.',
                'sensitive_content' => 'Murid menceritakan pernah dipermalukan saat salah membaca baris kode di depan kelas pada semester lalu.',
                'conclusion' => 'Kecemasan murid bersumber dari rasa takut dihakimi rekan sebaya. Dilakukan penguatan kepercayaan diri.',
                'follow_up_plan' => 'Praktik presentasi mikro 3 menit di hadapan Guru BK pada sesi berikutnya.',
            ],
            [
                'index' => 2,
                'nisn' => '0091234505', // Elsa Nadia Putri (X-RPL-1) - Konsultasi Mandiri
                'case_index' => null,
                'field' => 'pribadi',
                'status' => ServiceRecordStatus::NEW,
                'session_date' => '2026-09-15',
                'starts_at' => '10:15',
                'ends_at' => '11:00',
                'follow_up_date' => null,
                'topic' => 'Konsultasi adaptasi lingkungan baru bagi murid yang tinggal di indekos.',
                'general_summary' => null,
                'internal_note' => 'Identifikasi tingkat kemandirian murid baru yang jauh dari keluarga.',
                'sensitive_content' => 'Murid merasa homesick, sering menangis di malam hari dan pola makan tidak teratur.',
                'conclusion' => 'Perlu pendampingan adaptasi dan pembentukan rutinitas harian yang sehat.',
                'follow_up_plan' => 'Menghubungkan murid dengan komunitas teman sedaerah di sekolah.',
            ],
            [
                'index' => 3,
                'nisn' => '0091234511', // Kharisma Agung
                'case_index' => 3,
                'field' => 'pribadi',
                'status' => ServiceRecordStatus::NEEDS_FOLLOW_UP,
                'session_date' => '2026-09-12',
                'starts_at' => '13:00',
                'ends_at' => '14:00',
                'follow_up_date' => '2026-09-18',
                'topic' => 'Konseling ventilasi beban psikologis persoalan keluarga.',
                'general_summary' => 'Murid mengungkapkan keluhan situasi rumah dan menyepakati jadwal kehadiran orang tua ke sekolah.',
                'internal_note' => 'Kondisi emosional murid rentan meledak jika dipojokkan.',
                'sensitive_content' => 'Terjadi tekanan verbal di rumah terkait ekspektasi ekonomi keluarga.',
                'conclusion' => 'Murid membutuhkan ruang aman untuk berekspresi tanpa merasa disalahkan.',
                'follow_up_plan' => 'Mediasi segitiga antara murid, orang tua, dan konselor sekolah.',
            ],
            [
                'index' => 4,
                'nisn' => '0091234516', // Putri Maharani
                'case_index' => 4,
                'field' => 'pribadi',
                'status' => ServiceRecordStatus::COMPLETED,
                'session_date' => '2026-08-28',
                'starts_at' => '08:30',
                'ends_at' => '09:30',
                'follow_up_date' => null,
                'topic' => 'Debriefing hasil asesmen kecemasan dan penguatan kestabilan emosi.',
                'general_summary' => 'Murid telah mampu mengenali pemicu panik dan berhasil menguasai afirmasi positif saat menghadapi ujian kompetensi.',
                'internal_note' => 'Proses konseling berjalan tuntas dengan hasil evaluasi memuaskan.',
                'sensitive_content' => 'Murid menyadari bahwa nilai akademis bukan satu-satunya penentu harga dirinya.',
                'conclusion' => 'Murid mencapai kesadaran kognitif baru yang lebih realistis dan adaptif.',
                'follow_up_plan' => 'Selesai. Pemantauan berkala saat asesmen praktik berlangsung.',
            ],

            // ==================== BIDANG BELAJAR (4) ====================
            [
                'index' => 5,
                'nisn' => '0091234504', // Dimas Aryo Saputro
                'case_index' => 5,
                'field' => 'belajar',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'session_date' => '2026-09-11',
                'starts_at' => '11:00',
                'ends_at' => '12:00',
                'follow_up_date' => '2026-09-19',
                'topic' => 'Bimbingan strategi pemecahan masalah algoritma pemrograman dasar.',
                'general_summary' => 'Penyusunan jadwal pendampingan belajar tambahan bersama guru kejuruan dan tutor sebaya.',
                'internal_note' => 'Perlu visual diagram alir (flowchart) fisik untuk memudahkan pemahaman murid.',
                'sensitive_content' => 'Murid merasa malu bertanya di kelas karena ditertawakan teman saat keliru mengetik sintaks.',
                'conclusion' => 'Hambatan belajar diperparah oleh rasa rendah diri di hadapan teman sekelas.',
                'follow_up_plan' => 'Pemberian tugas coding bergradasi mudah-sedang bersama rekan belajar yang suportif.',
            ],
            [
                'index' => 6,
                'nisn' => '0091234508', // Hendra Setiawan
                'case_index' => 6,
                'field' => 'belajar',
                'status' => ServiceRecordStatus::NEW,
                'session_date' => '2026-09-15',
                'starts_at' => '09:30',
                'ends_at' => '10:15',
                'follow_up_date' => null,
                'topic' => 'Evaluasi kendala konsentrasi dan motivasi belajar pada mata pelajaran produktif.',
                'general_summary' => null,
                'internal_note' => 'Pengecekan kebiasaan belajar harian murid.',
                'sensitive_content' => 'Murid mengakui bermain game online hingga dini hari karena merasa jenuh dengan materi teori.',
                'conclusion' => 'Manajemen waktu dan disiplin diri murid memerlukan restrukturisasi mendesak.',
                'follow_up_plan' => 'Penyusunan lembar kontrol jam tidur dan pembatasan durasi gawai.',
            ],
            [
                'index' => 7,
                'nisn' => '0091234506', // Fajar Hidayat
                'case_index' => 7,
                'field' => 'belajar',
                'status' => ServiceRecordStatus::NEEDS_FOLLOW_UP,
                'session_date' => '2026-09-13',
                'starts_at' => '10:00',
                'ends_at' => '11:00',
                'follow_up_date' => '2026-09-20',
                'topic' => 'Konseling komitmen disiplin belajar dan penjadwalan istirahat murid.',
                'general_summary' => 'Pembahasan solusi adaptif agar tanggung jawab membantu keluarga tidak mengorbankan ketuntasan modul sekolah.',
                'internal_note' => 'Murid memiliki etos kerja tinggi, namun kelelahan fisik berdampak nyata pada kognisi belajar.',
                'sensitive_content' => 'Murid merasa bersalah jika tidak membantu orang tuanya yang sedang sakit mencari nafkah.',
                'conclusion' => 'Perlu koordinasi dengan guru produktif agar tugas proyek modul dapat dikerjakan secara cicil di lab.',
                'follow_up_plan' => 'Konsultasi lanjutan bersama wali kelas mengenai sistem modul mandiri.',
            ],
            [
                'index' => 8,
                'nisn' => '0091234513', // Muhamad Rizki
                'case_index' => 8,
                'field' => 'belajar',
                'status' => ServiceRecordStatus::COMPLETED,
                'session_date' => '2026-08-27',
                'starts_at' => '13:30',
                'ends_at' => '14:30',
                'follow_up_date' => null,
                'topic' => 'Bimbingan teknik penyusunan dokumentasi dan pelaporan tugas akhir berbasis SMK.',
                'general_summary' => 'Murid telah menyusun milestone pengerjaan laporan akhir dan menyelesaikan draf bab implementasi sistem.',
                'internal_note' => 'Pendampingan belajar menghasilkan peningkatan produktivitas yang signifikan.',
                'sensitive_content' => 'Kecemasan perfeksionis murid tereduksi setelah memahami prinsip iterasi bertahap.',
                'conclusion' => 'Murid mandiri dan siap mempresentasikan karyanya.',
                'follow_up_plan' => 'Selesai.',
            ],

            // ==================== BIDANG SOSIAL (4) ====================
            [
                'index' => 9,
                'nisn' => '0091234503', // Citra Dewi Lestari
                'case_index' => 9,
                'field' => 'sosial',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'session_date' => '2026-09-12',
                'starts_at' => '08:30',
                'ends_at' => '09:30',
                'follow_up_date' => '2026-09-19',
                'topic' => 'Mediasi komunikasi interpersonal dan resolusi konflik teman sebaya.',
                'general_summary' => 'Diskusi netral untuk membedah kesalahpahaman isi percakapan grup chat kelas.',
                'internal_note' => 'Kedua pihak telah mulai menunjukkan keterbukaan untuk saling memaafkan.',
                'sensitive_content' => 'Murid merasa sangat terisolasi karena tidak diajak saat jam makan siang.',
                'conclusion' => 'Dinamika kelompok kelas membutuhkan bimbingan klasikal tentang empati komunikasi digital.',
                'follow_up_plan' => 'Sesi mediasi bersama perwakilan rekan sekelas.',
            ],
            [
                'index' => 10,
                'nisn' => '0091234507', // Gita Permatasari
                'case_index' => 10,
                'field' => 'sosial',
                'status' => ServiceRecordStatus::NEW,
                'session_date' => '2026-09-16',
                'starts_at' => '10:00',
                'ends_at' => '10:45',
                'follow_up_date' => null,
                'topic' => 'Asesmen keterampilan komunikasi asertif dalam kerja kelompok lab.',
                'general_summary' => null,
                'internal_note' => 'Eksplorasi kendala pembagian tugas praktikum kejuruan.',
                'sensitive_content' => 'Murid merasa selalu dieksploitasi untuk mengerjakan seluruh kodingan tanpa kontribusi rekan kelompok.',
                'conclusion' => 'Murid membutuhkan latihan menyatakan penolakan secara santun namun tegas (asertif).',
                'follow_up_plan' => 'Role playing komunikasi asertif pembagian jobdesk kelompok.',
            ],
            [
                'index' => 11,
                'nisn' => '0091234519', // Tegar Bagas Nugroho
                'case_index' => 11,
                'field' => 'sosial',
                'status' => ServiceRecordStatus::NEEDS_FOLLOW_UP,
                'session_date' => '2026-09-14',
                'starts_at' => '11:15',
                'ends_at' => '12:15',
                'follow_up_date' => '2026-09-21',
                'topic' => 'Bimbingan pengendalian amarah (anger management) dan resolusi damai.',
                'general_summary' => 'Pembahasan tata nilai persaudaraan di lingkungan sekolah dan konsekuensi sanksi perkelahian.',
                'internal_note' => 'Murid telah menunjukkan rasa penyesalan atas tindakan emosionalnya.',
                'sensitive_content' => 'Murid mudah terpicu emosi jika martabat kelompok atau daerah asalnya disinggung.',
                'conclusion' => 'Dibutuhkan latihan self-talk dan jeda respon (pause technique) saat menghadapi provokasi.',
                'follow_up_plan' => 'Monitoring mingguan catatan kedisiplinan murid.',
            ],
            [
                'index' => 12,
                'nisn' => '0091234521', // Vito Erlangga
                'case_index' => 12,
                'field' => 'sosial',
                'status' => ServiceRecordStatus::COMPLETED,
                'session_date' => '2026-09-03',
                'starts_at' => '09:00',
                'ends_at' => '10:00',
                'follow_up_date' => null,
                'topic' => 'Pembinaan empati sosial dan pemulihan hubungan pertemanan.',
                'general_summary' => 'Murid secara tulus menyadari kekeliruan perkataan bernada ejekan dan telah meminta maaf langsung.',
                'internal_note' => 'Hubungan antarmurid kembali harmonis di kelas.',
                'sensitive_content' => 'Murid mengaku melakukan celaan semata-mata demi mencairkan suasana namun berujung kebablasan.',
                'conclusion' => 'Murid memahami batasan humor yang sehat tanpa merendahkan sesama teman.',
                'follow_up_plan' => 'Selesai.',
            ],

            // ==================== BIDANG KARIER (3) ====================
            [
                'index' => 13,
                'nisn' => '0091234510', // Joko Santoso
                'case_index' => 13,
                'field' => 'karier',
                'status' => ServiceRecordStatus::IN_PROGRESS,
                'session_date' => '2026-09-11',
                'starts_at' => '13:00',
                'ends_at' => '14:00',
                'follow_up_date' => '2026-09-18',
                'topic' => 'Eksplorasi profil industri mitra sekolah untuk penempatan PKL semester depan.',
                'general_summary' => 'Pemetaan opsi industri software house yang sesuai dengan minat fokus keahlian backend.',
                'internal_note' => 'Mengarahkan murid menyusun resume portofolio GitHub sebelum masa pendaftaran PKL dibuka.',
                'sensitive_content' => 'Murid merasa khawatir tidak mampu bersaing dengan siswa sekolah lain saat seleksi magang.',
                'conclusion' => 'Kompetensi teknis murid mencukupi; dibutuhkan penguatan motivasi dan kesiapan mental.',
                'follow_up_plan' => 'Review bersama berkas lamaran dan contoh proyek coding murid.',
            ],
            [
                'index' => 14,
                'nisn' => '0091234512', // Laila Nurfadillah
                'case_index' => 14,
                'field' => 'karier',
                'status' => ServiceRecordStatus::NEEDS_FOLLOW_UP,
                'session_date' => '2026-09-15',
                'starts_at' => '10:30',
                'ends_at' => '11:30',
                'follow_up_date' => '2026-09-22',
                'topic' => 'Konseling perencanaan studi lanjut vokasi dan pemanfaatan beasiswa pemerintah.',
                'general_summary' => 'Pemberian informasi rinci skema KIP-Kuliah dan politeknik negeri bidang teknologi informasi.',
                'internal_note' => 'Mempersiapkan data pendukung untuk sesi konsultasi bersama orang tua.',
                'sensitive_content' => 'Murid merasa tertekan karena orang tua menginginkan kontribusi penghasilan instan setelah lulus.',
                'conclusion' => 'Diperlukan dialog rasional mengenai prospek karir lulusan vokasi D4 yang lebih menjanjikan.',
                'follow_up_plan' => 'Pertemuan daring/luring bersama orang tua murid.',
            ],
            [
                'index' => 15,
                'nisn' => '0091234517', // Rafi Ahmad Fauzi
                'case_index' => 15,
                'field' => 'karier',
                'status' => ServiceRecordStatus::COMPLETED,
                'session_date' => '2026-08-30',
                'starts_at' => '14:00',
                'ends_at' => '15:00',
                'follow_up_date' => null,
                'topic' => 'Simulasi akhir wawancara kerja industri dan pembekalan etika magang.',
                'general_summary' => 'Murid telah menguasai cara menjawab pertanyaan teknis jaringan dan memiliki kesiapan mental prima.',
                'internal_note' => 'Latihan mock interview memberikan dampak peningkatan rasa percaya diri yang tinggi.',
                'sensitive_content' => 'Murid berhasil mengatasi rasa gugup berbicara di depan asesor eksternal.',
                'conclusion' => 'Murid dinyatakan siap terjun ke lingkungan industri nyata.',
                'follow_up_plan' => 'Selesai. Dilanjutkan monitoring oleh pembimbing PKL.',
            ],
        ];

        $legacyStatusId = ReferenceValue::query()
            ->where('category', 'consultation_status')
            ->where('code', ServiceRecordStatus::COMPLETED)
            ->valueOrFail('id');

        foreach ($definitions as $def) {
            $student = Student::query()->where('nisn', $def['nisn'])->firstOrFail();
            $regNumber = sprintf('KNS-%s-%04d', substr($def['session_date'], 0, 4), $def['index']);

            /** @var Consultation $consultation */
            $consultation = Consultation::query()->firstOrNew(['registration_number' => $regNumber]);

            // ponytail: status_id/topic legacy masih NOT NULL sampai migration pembersihan; hapus bersama kolomnya.
            $consultation->forceFill([
                'registration_number' => $regNumber,
                'student_id' => $student->id,
                'temporary_student_id' => null,
                'service_field_id' => $fields[$def['field']],
                'session_date' => $def['session_date'],
                'problem' => $def['topic'],
                'handling' => $def['general_summary'] ?? 'Pendampingan sesuai kebutuhan murid.',
                'result' => $def['conclusion'],
                'counselor_id' => $guruBk->id,
                'status_id' => $legacyStatusId,
                'topic' => $def['topic'],
            ])->save();
        }
    }
}
