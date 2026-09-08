<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\CaseAssignment;
use App\Models\CaseCoordination;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExternalTatibRecord;
use App\Models\FollowUp;
use App\Models\Student;
use App\Models\StudentClassMembership;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\QaDataResetter;
use Database\Seeders\QaDataSeeder;
use Illuminate\Console\Command;

class QaDataCommand extends Command
{
    protected $signature = 'sibk:qa-data {action=status : Aksi yang dijalankan: seed, reset, atau status}';

    protected $description = 'Kelola database dummy khusus QA/Local Testing (seed, reset, status).';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Perintah ini hanya tersedia pada environment local atau testing.');

            return self::FAILURE;
        }

        $action = strtolower((string) $this->argument('action'));

        return match ($action) {
            'seed' => $this->handleSeed(),
            'reset' => $this->handleReset(),
            'status' => $this->handleStatus(),
            default => $this->handleInvalidAction($action),
        };
    }

    private function handleSeed(): int
    {
        $this->components->info('Menyiapkan dataset dummy QA lokal...');

        try {
            $seeder = app(QaDataSeeder::class);
            $seeder->setContainer(app())->setCommand($this)->run();
        } catch (\Throwable $e) {
            $this->error('Gagal menjalankan seeder QA: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Dataset dummy QA berhasil dipopulasikan!');
        $this->handleStatus();

        return self::SUCCESS;
    }

    private function handleReset(): int
    {
        $this->components->warn('Membersihkan seluruh data berpenanda QA...');

        try {
            QaDataResetter::reset();
        } catch (\Throwable $e) {
            $this->error('Gagal membersihkan data QA: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('Data dummy QA berhasil dibersihkan! Data developer tetap aman.');
        $this->handleStatus();

        return self::SUCCESS;
    }

    private function handleStatus(): int
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>=== STATISTIK DATA KHUSUS QA ===</>');

        $counts = [
            ['Entitas', 'Jumlah Data QA', 'Filter Identifikasi'],
            ['Akun Pengguna (Users)', User::query()->where('email', 'like', '%qa%@ruangbk.test')->count(), 'email: *qa*@ruangbk.test'],
            ['Tahun Ajaran (Academic Years)', AcademicYear::query()->where('dapodik_id', 'like', 'QA-AY-%')->count(), 'dapodik_id: QA-AY-*'],
            ['Rombel / Kelas (Classrooms)', Classroom::query()->where('dapodik_id', 'like', 'QA-CLS-%')->count(), 'dapodik_id: QA-CLS-*'],
            ['Penugasan Guru (Teacher Assignments)', TeacherAssignment::query()->where('decision_number', 'like', 'QA-SK-%')->count(), 'decision_number: QA-SK-*'],
            ['Murid (Students)', Student::query()->where('dapodik_id', 'like', 'QA-STU-%')->count(), 'dapodik_id: QA-STU-* (NISN 9926*)'],
            ['Penempatan Kelas (Memberships)', StudentClassMembership::query()->where('dapodik_id', 'like', 'QA-MEM-%')->count(), 'dapodik_id: QA-MEM-*'],
            ['Catatan e-Tatib (External Tatib)', ExternalTatibRecord::query()->where('source_identifier', 'like', 'QA-ETATIB-%')->count(), 'source_identifier: QA-ETATIB-*'],
            ['Kasus BK (Cases)', BkCase::query()->where('registration_number', 'like', 'K-QA-%')->count(), 'registration_number: K-QA-*'],
            ['Penugasan Kasus (Case Assignments)', CaseAssignment::query()->whereHas('case', fn ($q) => $q->where('registration_number', 'like', 'K-QA-%'))->count(), 'case: K-QA-*'],
            ['Koordinasi Kasus (Case Coordinations)', CaseCoordination::query()->whereHas('case', fn ($q) => $q->where('registration_number', 'like', 'K-QA-%'))->count(), 'case: K-QA-*'],
            ['Tindak Lanjut (Follow Ups)', FollowUp::query()->whereHas('case', fn ($q) => $q->where('registration_number', 'like', 'K-QA-%'))->count(), 'case: K-QA-*'],
            ['Konsultasi (Consultations)', Consultation::query()->where('registration_number', 'like', 'KNS-QA-%')->count(), 'registration_number: KNS-QA-*'],
            ['Prestasi Murid (Achievements)', Achievement::query()->where('evidence_reference', 'like', 'QA-EVD-%')->count(), 'evidence_reference: QA-EVD-*'],
        ];

        $this->table(['Entitas', 'Jumlah Data QA', 'Pola Pengenal'], array_slice($counts, 1));

        $this->newLine();
        $this->line('<fg=cyan;options=bold>=== DAFTAR AKUN QA TERSEDIA ===</>');

        $accounts = [
            ['Role', 'Nama', 'Email', 'Status', 'Cakupan Akses'],
            ['Guru BK', 'Guru BK QA A', 'guru.bk.qa.a@ruangbk.test', 'Aktif', 'Rombel X PPLG 1 & XI PPLG 1 + Kasus Khusus Gilang'],
            ['Guru BK', 'Guru BK QA B', 'guru.bk.qa.b@ruangbk.test', 'Aktif', 'Rombel X PPLG 2 & XII PPLG 1'],
            ['Guru BK', 'Guru BK QA C', 'guru.bk.qa.c@ruangbk.test', 'Aktif', 'Tanpa rombel (Kasus khusus Ilham Hidayat)'],
            ['Koordinator BK', 'Koordinator BK QA', 'koordinator.bk.qa@ruangbk.test', 'Aktif', 'Supervisi seluruh data; verifikasi prestasi; sensor catatan privat'],
            ['Waka Kesiswaan', 'Waka Kesiswaan QA', 'waka.kesiswaan.qa@ruangbk.test', 'Aktif', 'Read-only kasus terkoordinasi (Rizky Maulana); prestasi terverifikasi'],
            ['Admin IT', 'Admin IT QA', 'admin.it.qa@ruangbk.test', 'Aktif', 'Kelola akun & data master teknis; dilarang akses data layanan BK'],
            ['Guru BK', 'Guru BK QA Nonaktif', 'guru.bk.qa.nonaktif@ruangbk.test', 'Nonaktif', 'Uji penolakan otentikasi login akun dinonaktifkan'],
        ];

        $this->table(['Role', 'Nama', 'Email', 'Status', 'Cakupan Akses'], array_slice($accounts, 1));
        $this->comment('Kata sandi seluruh akun QA di atas: 12345678 (dari SIBK_SEED_ACCOUNT_PASSWORD)');
        $this->newLine();

        return self::SUCCESS;
    }

    private function handleInvalidAction(string $action): int
    {
        $this->error("Aksi '{$action}' tidak dikenal. Gunakan: seed, reset, atau status.");

        return self::FAILURE;
    }
}
