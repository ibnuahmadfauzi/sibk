# Penyederhanaan Laporan dan Operasional Ruang BK Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyederhanakan laporan Guru BK/Koordinator, lifecycle layanan, proses keluar murid, pengelolaan akun, dashboard, dan hasil akhir skema database tanpa menambah framework tabel atau sumber data ganda.

**Architecture:** Fase A menjadikan `/reports` halaman server-rendered bertab yang memakai request/service rekap khusus. Fase B mempertahankan tabel domain sebagai sumber kebenaran, mengganti koreksi terminal dengan edit beralasan yang diaudit, memakai soft delete untuk arsip, menyimpan proses keluar murid pada satu tabel berstatus, serta memisahkan lifecycle password sementara dari pengelolaan profil akun. Policy, Form Request, service fokus, transaksi, dan constraint database tetap menjadi batas antarlapisan.

**Tech Stack:** PHP 8.3, Laravel 13.25, Eloquent, Form Request, Blade, Bootstrap 5.3.8, SCSS existing, JavaScript ringan existing, Laravel Pagination, PHPUnit 12.5.

**Spec:**
- `docs/superpowers/specs/2026-09-14-penyederhanaan-laporan-guru-koordinator-design.md`
- `docs/superpowers/specs/2026-09-14-penyederhanaan-operasional-akun-dan-skema-data-design.md`

## Global Constraints

- Jangan menambahkan Spatie Query Builder, Yajra DataTables, Livewire Tables, Livewire PowerGrid, Filament Tables, jQuery, Alpine, atau framework tabel lain.
- Pertahankan tujuh nilai `type` pada `GET /reports/preview?type=...` dan
  `GET /reports/export?type=...&format=csv`. `ReportRequest`/`ReportService`
  tetap melayani mode ini; halaman `/reports` hanya mengganti katalog UI dengan
  tiga tab dan tidak menghapus consumer legacy.
- `GET /reports` memakai tab `pelanggaran|layanan|prestasi`; tab default adalah `layanan`.
- Guru BK hanya menerima data dalam scope profesional/kasus khusus; Koordinator BK menerima rekap gabungan; Admin IT dan Waka murni ditolak dari laporan BK. Waka Kesiswaan tetap mempunyai akses baca ke daftar dan detail operasional proses keluar murid sesuai kewenangannya.
- View dan CSV hanya menerima inisial murid, NISN tersamarkan, kelas historis, dan nilai agregat aman.
- Jangan mengirim model Eloquent mentah ke Blade.
- Query tabel dan CSV wajib memakai filter serta scope yang sama.
- Pagination memakai 20 baris dan query string dipertahankan.
- Pagination dilakukan pada query identitas/agregat di database; jangan mengambil seluruh dataset ke Collection hanya untuk memotong 20 baris.
- Identitas sementara yang belum direkonsiliasi hanya muncul pada tab Layanan bila actor berwenang; grouping memakai ID, bukan nama.
- Semua PHP baru memakai `declare(strict_types=1);`.
- Gunakan Bahasa Indonesia dan istilah `murid`.
- Task 1-7 tidak membuat migration. Task 8 dan seterusnya hanya membuat migration lanjutan yang disebutkan pada plan; file migration lama tidak dihapus atau ditulis ulang. Migration baru bersifat forward-only: `down()` tidak memulihkan data, reference, atau tabel retired.
- `migrate:fresh --seed` boleh dipakai pada database lokal/pengembangan selama target sudah dipastikan bukan shared/production. Reset, rollback destruktif, atau purge tetap dilarang pada database shared/production.
- Angka 30 tabel hanya sasaran penyederhanaan, bukan acceptance criterion. Pertahankan `sessions`, `cache`, `cache_locks`, `audit_logs`, dan tabel lain yang belum aman dihapus; ukuran keberhasilan adalah satu sumber kebenaran, consumer jelas, dan tidak ada skema spaghetti.
- Queue MVP memakai `QUEUE_CONNECTION=sync`; jangan membuat adapter production Dapodik/e-Tatib pada plan ini.
- API sekolah hanya menjadi sumber roster dan pelanggaran sesuai field yang tersedia; API tidak menentukan status keluar murid.
- Tidak ada perubahan Penpot atau format XLSX/PDF server.
- Seluruh automated test dan verification dijalankan hanya melalui CLI. Gate
  otomatis tidak boleh memakai browser automation, headless browser, CUA,
  screenshot comparison, atau penilaian visual berbasis model.
- `FrontendPreviewTest`, `scripts/check-frontend.mjs`, dan build Vite tetap
  termasuk gate CLI karena memeriksa kontrak route/HTML/source/build, bukan
  kualitas tampilan visual.
- Tampilan responsif, overflow, focus state yang terlihat, modal/konfirmasi,
  kenyamanan interaksi, dan hasil cetak diuji manual oleh pengguna atau tester
  manusia melalui browser biasa. Agent tidak boleh menandai UAT manual PASS
  tanpa hasil yang dilaporkan pelaksana manual.

## Hasil Audit Checkpoint 3 — 15 September 2026

Status faktual pada branch `checkpoint-3-rbac`: seluruh checkbox Task 1-15
masih kosong. Pipeline rekap tiga tab, proses keluar murid, password sementara,
dan target skema belum diimplementasikan; lifecycle/route dasar yang sudah ada
bukan bukti Task 9-15 telah selesai. Tabel ini adalah audit plan, bukan bukti
task telah selesai.

| Task | Hasil audit dan kontrak antartask |
|---|---|
| 1 | Tetap menjadi sumber kontrak tab dan legacy sebelum perubahan kode. |
| 2 | Memisahkan `OperationalReportRequest` dari `ReportRequest`; request lama tetap untuk preview dan ekspor legacy berbasis `type`. |
| 3 | Bergantung pada fondasi filter dan output aman Task 2. |
| 4 | Bergantung pada Task 2; identitas sementara tetap khusus tab Layanan. |
| 5 | Bergantung pada Task 2; tidak menambah sumber data rekap. |
| 6 | Menghubungkan tiga builder Task 3-5 sambil memilih pipeline ekspor tab atau legacy secara eksplisit. |
| 7 | Memverifikasi Task 1-6 dan menghentikan fase laporan pada `PENDING MANUAL` sampai UAT manusia tersedia. |
| 8 | Mengunci requirement operasional sebelum perubahan lifecycle dan skema Task 9-13. |
| 9 | Menjadi prasyarat soft delete dan status layanan untuk Task 10, 13, dan 14; migration dibuat forward-only. |
| 10 | Menghapus fitur retired berikut bookmark `_preview` terkait, sambil mempertahankan audit backend dan preview legacy lain. |
| 11 | Menyediakan satu proses keluar murid serta scope layanan aktif untuk Task 14. |
| 12 | Menambahkan password sementara tanpa mengirim nilai password ke audit atau log. |
| 13 | Mengaudit kesehatan skema setelah Task 10. Tidak menghapus tabel fisik pada checkpoint ini; kandidat retired dipertahankan bila penghapusan belum mempunyai bukti aman, backup, dan jalur pemulihan. |
| 14 | Menyatukan scope Task 9 dan 11 ke laporan, dashboard, serta Portal Waka tanpa mengubah route Waka. |
| 15 | Menggabungkan gate Task 1-14; tidak dapat menutup plan sebelum hasil UAT manual PASS. |

Keputusan route hasil audit:

| Route/kelompok | Keputusan |
|---|---|
| `GET /reports` | Dipertahankan sebagai halaman tiga tab (`pelanggaran`, `layanan`, `prestasi`). |
| `GET /reports/preview?type=...` | Dipertahankan untuk tujuh `type` legacy. |
| `GET /reports/export?type=...&format=csv` | Dipertahankan; mode ini tidak boleh bercampur dengan `tab`. |
| `GET /reports/export?tab=...&format=csv` | Ditambahkan sebagai ekspor tiga tab. |
| `GET /students/show?nisn={nisn}&tab=...` dan `/_preview/students/show` | Dipertahankan sebagai bookmark legacy yang melakukan policy check lalu redirect ke `/students/{student}` dengan tab tervalidasi. |
| `GET /waka/reports`, `GET /waka/handling-reports`, dan `GET /waka/handling-reports/export` | Dipertahankan dengan policy, redirect, dan kontrak CSV Waka yang ada; Task 6 tidak memakai route ekspor ini. |
| Preview Dapodik serta `/_preview` selain fitur retired | Dipertahankan sesuai destination legacy saat ini. |
| `/corrections`, `/notifications`, `/history`, `_preview/notifications`, `_preview/corrections*`, `_preview/history` | Dihentikan pada Task 10 dan wajib 404/tidak terdaftar. |

---

### Task 1: Selaraskan Requirement dan Kontrak Laporan

**Files:**
- Modify: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements-index.md`
- Modify: `docs/api-contract.md`
- Modify: `docs/superpowers/specs/2026-09-14-penyederhanaan-laporan-guru-koordinator-design.md`

**Interfaces:**
- Consumes: keputusan produk pada spec.
- Produces: kontrak tiga tab, filter, kolom, scope, privasi, dan kompatibilitas legacy untuk seluruh task berikutnya.

- [x] **Step 1: Amendemen PRD v1.1**

Ubah bagian `Arsitektur informasi dan laporan` serta `Laporan P0` agar menyatakan:

```text
Guru BK dan Koordinator BK memakai satu halaman Laporan bertab:
Pelanggaran & Poin, Layanan BK, dan Prestasi. Setiap tab menampilkan
rekap satu baris per murid; identitas sementara yang sah hanya dapat
muncul pada tab Layanan. Tujuh tipe laporan lama tidak lagi menjadi
katalog navigasi, tetapi kontrak URL-nya dipertahankan sementara.
```

- [x] **Step 2: Amendemen REP-01 sampai REP-04**

Kunci acceptance criteria berikut pada SRS:

```text
REP-01: filter tab menggunakan nama murid, tahun ajaran, periode, kelas,
dan Guru BK khusus Koordinator pada tab Layanan.
REP-02: pelanggaran/poin, layanan, dan prestasi tersedia sebagai tiga
rekap per murid tanpa penggabungan file manual.
REP-03: tabel, cetak, dan CSV memakai dataset terscope serta identitas
tersamarkan yang sama.
REP-04: Guru BK hanya memperoleh scope sah; Koordinator memperoleh rekap
gabungan dan filter Guru BK yang tidak memperluas akses.
```

- [x] **Step 3: Perbarui kontrak endpoint**

Tambahkan ke `docs/api-contract.md`:

```text
GET /reports?tab=pelanggaran|layanan|prestasi
GET /reports/export?tab=pelanggaran|layanan|prestasi&format=csv
```

Dokumentasikan bahwa mode `tab` dan mode legacy `type` tidak boleh dikirim bersamaan. `GET /reports/preview?type=...` tetap legacy-compatible.

Tambahkan riwayat amandemen 14 September 2026 pada PRD/SRS v1.1 tanpa mengganti nama file versi, dan nyatakan bahwa filter Guru BK pada tab Layanan mengikuti penanggung jawab kasus yang efektif pada tanggal layanan/tindak lanjut serta `consultations.counselor_id`, bukan sekadar pengguna yang pertama membuat atau terakhir mencatat record.

- [x] **Step 4: Catat keputusan teknologi**

Dokumentasikan bahwa implementasi memakai Eloquent, Form Request, Blade, Bootstrap, SCSS, dan Laravel Pagination tanpa dependency tabel baru.

- [x] **Step 5: Verifikasi dokumen**

```powershell
rg -n "Pelanggaran & Poin|Layanan BK|Prestasi|satu baris per murid|REP-01|REP-04|mode legacy|Laravel Pagination" docs/requirements docs/requirements-index.md docs/api-contract.md docs/superpowers/specs/2026-09-14-penyederhanaan-laporan-guru-koordinator-design.md
git diff --check
```

Expected: istilah dan kontrak konsisten; PRD/SRS v1.0 di repository arsip privat tidak berubah.

- [x] **Step 6: Commit**

```powershell
git add docs/requirements/PRD_Aplikasi_BK_v1.1.md docs/requirements/SRS_Aplikasi_BK_v1.1.md docs/requirements-index.md docs/api-contract.md docs/superpowers/specs/2026-09-14-penyederhanaan-laporan-guru-koordinator-design.md
git commit -m "docs: sederhanakan kontrak laporan Guru BK"
```

---

### Task 2: Bangun Fondasi Filter dan Presentasi Aman

**Files:**
- Create: `app/Contracts/OperationalReportRecap.php`
- Create: `app/Http/Requests/OperationalReportRequest.php`
- Create: `app/Services/OperationalReportRecapService.php`
- Modify: `app/Http/Controllers/ReportController.php`
- Modify: `app/Http/Requests/ReportRequest.php`
- Modify: `app/Policies/ReportPolicy.php`
- Test: `tests/Feature/OperationalReportRecapTest.php`

**Interfaces:**
- Consumes: `ReportPolicy::viewAny(User $user): bool`, `AcademicYear`, `Classroom`, dan scope `Student::professionallyAccessibleTo()`.
- Produces: `OperationalReportRecap`, `OperationalReportRequest::filters(): array`, `ReportPolicy::viewTab()`, serta public dispatch/helper/filter context pada `OperationalReportRecapService`.

- [x] **Step 1: Tulis failing test authorization dan validasi**

Buat `OperationalReportRecapTest` dengan `RefreshDatabase` dan fondasi fixture berikut:

```php
private AcademicYear $year;

protected function setUp(): void
{
    parent::setUp();
    $this->travelTo('2026-08-20 10:00:00');
    $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    $this->year = AcademicYear::query()->create([
        'name' => '2026/2027', 'starts_on' => '2026-07-01',
        'ends_on' => '2027-06-30', 'is_active' => true,
    ]);
}

private function userWithRole(string $slug, string $name): User
{
    $user = User::factory()->create(['name' => $name]);
    $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

    return $user;
}

private function reference(string $category, string $code): ReferenceValue
{
    return ReferenceValue::query()->where('category', $category)->where('code', $code)->firstOrFail();
}

/** @return array{Student, Classroom} */
private function scopedStudent(User $teacher, string $name, string $nisn, string $className): array
{
    $classroom = Classroom::query()->create([
        'academic_year_id' => $this->year->id, 'name' => $className, 'is_active' => true,
    ]);
    $student = Student::query()->create(['nisn' => $nisn, 'name' => $name, 'is_active' => true]);
    StudentClassMembership::query()->create([
        'student_id' => $student->id, 'classroom_id' => $classroom->id,
        'academic_year_id' => $this->year->id, 'effective_from' => '2026-07-01', 'is_active' => true,
    ]);
    TeacherAssignment::query()->create([
        'user_id' => $teacher->id, 'classroom_id' => $classroom->id,
        'academic_year_id' => $this->year->id, 'effective_from' => '2026-07-01',
        'decision_number' => 'SK-'.$classroom->id, 'assigned_by' => $teacher->id,
    ]);

    return [$student, $classroom];
}
```

Tambahkan test konkret berikut:

```php
public function test_operational_report_tabs_follow_role_authorization(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru Laporan');
    $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator Laporan');
    $waka = $this->userWithRole('waka_kesiswaan', 'Waka Laporan');
    $admin = $this->userWithRole('admin_it', 'Admin Laporan');

    $this->get(route('reports.index'))->assertRedirect(route('login'));
    $this->actingAs($teacher)->get(route('reports.index'))->assertOk();
    $this->actingAs($coordinator)->get(route('reports.index', ['tab' => 'prestasi']))->assertOk();
    $this->actingAs($waka)->get(route('reports.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('reports.index'))->assertForbidden();
}

public function test_request_rejects_invalid_dates_and_class_outside_year_or_scope(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru Filter');
    [, $allowedClass] = $this->scopedStudent($teacher, 'Murid Filter', '0012345678', 'X RPL 1');
    $otherYear = AcademicYear::query()->create([
        'name' => '2025/2026', 'starts_on' => '2025-07-01', 'ends_on' => '2026-06-30', 'is_active' => false,
    ]);
    $crossYearClass = Classroom::query()->create([
        'academic_year_id' => $otherYear->id, 'name' => 'X Lama', 'is_active' => true,
    ]);

    $this->actingAs($teacher)->get(route('reports.index', [
        'tab' => 'pelanggaran', 'academic_year_id' => $this->year->id,
        'date_start' => '2026-08-20', 'date_end' => '2026-08-01',
        'classroom_id' => $crossYearClass->id,
    ]))->assertSessionHasErrors(['date_end', 'classroom_id']);

    $this->actingAs($teacher)->get(route('reports.index', [
        'tab' => 'pelanggaran', 'academic_year_id' => $this->year->id,
        'classroom_id' => $allowedClass->id,
    ]))->assertOk();
}

public function test_counselor_filter_is_validated_safely(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru Biasa');
    $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
    $activeCounselor = $this->userWithRole('guru_bk', 'Guru Aktif');
    $inactiveCounselor = $this->userWithRole('guru_bk', 'Guru Nonaktif');
    $inactiveCounselor->update(['is_active' => false]);

    $this->actingAs($teacher)->get(route('reports.index', [
        'tab' => 'layanan', 'counselor_id' => $activeCounselor->id,
    ]))->assertSessionHasErrors('counselor_id');
    $this->actingAs($coordinator)->get(route('reports.index', [
        'tab' => 'prestasi', 'counselor_id' => $activeCounselor->id,
    ]))->assertSessionHasErrors('counselor_id');
    $this->actingAs($coordinator)->get(route('reports.index', [
        'tab' => 'layanan', 'counselor_id' => $inactiveCounselor->id,
    ]))->assertSessionHasErrors('counselor_id');
    $this->actingAs($coordinator)->get(route('reports.index', [
        'tab' => 'layanan', 'counselor_id' => $activeCounselor->id,
    ]))->assertOk();
}
```

Test membuktikan guest/role authorization, kelas lintas tahun dan di luar scope ditolak, serta Guru BK filter harus berupa akun aktif dengan role `guru_bk`.

- [x] **Step 2: Jalankan test dan pastikan gagal**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php --filter="authorization|request"
```

Expected: FAIL karena request, policy method, dan service belum tersedia.

- [x] **Step 3: Implementasikan request tab**

Gunakan kontrak berikut:

```php
final class OperationalReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ReportPolicy::class)->viewAny($user);
    }

    public function rules(): array
    {
        return [
            'tab' => [Rule::requiredIf($this->routeIs('reports.index')), 'nullable', Rule::in(OperationalReportRecapService::tabs())],
            'type' => ['nullable', Rule::in(ReportService::types())],
            'q' => ['nullable', 'string', 'max:100', 'not_regex:/[\\x00-\\x1F\\x7F]/u'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'counselor_id' => ['nullable', 'integer', 'exists:users,id'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'service_field_id' => ['nullable', 'integer', 'exists:references,id'],
            'status_id' => ['nullable', 'integer', 'exists:references,id'],
            'achievement_type_id' => ['nullable', 'integer', 'exists:references,id'],
            'achievement_level_id' => ['nullable', 'integer', 'exists:references,id'],
            'minimum_points' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'format' => [Rule::requiredIf($this->routeIs('reports.export')), 'nullable', Rule::in(['csv'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): array
    {
        $data = $this->safe()->except('format');

        return isset($data['tab'])
            ? Arr::only($data, ['tab', 'q', 'academic_year_id', 'date_start', 'date_end', 'classroom_id', 'counselor_id', 'page'])
            : Arr::only($data, ['type', 'academic_year_id', 'date_start', 'date_end', 'classroom_id', 'student_id', 'category', 'service_field_id', 'status_id', 'achievement_type_id', 'achievement_level_id', 'counselor_id', 'minimum_points', 'page']);
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $hasTab = $this->filled('tab');
            $hasType = $this->filled('type');

            if ($this->routeIs('reports.index') && $hasType) {
                $validator->errors()->add('type', 'Mode laporan lama tidak dapat digabungkan dengan tab.');
            }
            if ($this->routeIs('reports.export') && $hasTab === $hasType) {
                $validator->errors()->add('tab', 'Pilih tepat satu jenis laporan untuk diekspor.');
                $validator->errors()->add('type', 'Pilih tepat satu jenis laporan untuk diekspor.');
            }

            if (! $hasTab) {
                return;
            }

            $actor = $this->user();
            $year = $this->filled('academic_year_id')
                ? AcademicYear::query()->find($this->integer('academic_year_id'))
                : AcademicYear::query()->active()->orderByDesc('starts_on')->first()
                    ?? AcademicYear::query()->orderByDesc('starts_on')->first();

            if ($this->filled('classroom_id')) {
                $allowed = Classroom::query()
                    ->whereKey($this->integer('classroom_id'))
                    ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                        ->where('academic_year_id', $selected->id))
                    ->whereHas('studentClassMemberships.student', fn (Builder $students): Builder => $students
                        ->accessibleTo($actor))
                    ->exists();
                if (! $allowed) {
                    $validator->errors()->add('classroom_id', 'Kelas tidak tersedia untuk laporan ini.');
                }
            }

            if ($this->filled('counselor_id')) {
                $allowed = $actor?->hasRole('koordinator_bk') === true
                    && $this->string('tab')->toString() === OperationalReportRecapService::TAB_SERVICES
                    && User::query()->active()->whereKey($this->integer('counselor_id'))
                        ->whereHas('roles', fn (Builder $roles): Builder => $roles
                            ->where('slug', 'guru_bk')->where('is_active', true))
                        ->exists();
                if (! $allowed) {
                    $validator->errors()->add('counselor_id', 'Guru BK tidak tersedia untuk laporan ini.');
                }
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('reports.index') && ! $this->has('tab')) {
            $this->merge(['tab' => OperationalReportRecapService::TAB_SERVICES]);
        }
    }

    protected function getRedirectUrl(): string
    {
        $tab = $this->string('tab')->toString();
        if (in_array($tab, OperationalReportRecapService::tabs(), true)) {
            return route('reports.index', ['tab' => $tab]);
        }

        $type = $this->string('type')->toString();
        if ($this->routeIs('reports.export') && in_array($type, ReportService::types(), true)) {
            return route('reports.preview', ['type' => $type]);
        }

        return route('reports.index', ['tab' => OperationalReportRecapService::TAB_SERVICES]);
    }
}
```

Import `App\Models\AcademicYear`, `App\Models\Classroom`, `App\Models\User`, `App\Services\ReportService`, `Illuminate\Database\Eloquent\Builder`, `Illuminate\Support\Arr`, dan `Illuminate\Validation\Validator`. Pada controller export ambil mode/filter dari `$request->filters()` dan format dari `$request->validated('format')`, sehingga filter legacy tetap diteruskan tetapi parameter milik mode lain dibuang setelah validasi.
Authorization request hanya memeriksa capability laporan umum agar nilai `tab`/`type` yang invalid menghasilkan pesan validasi, bukan 403. Setelah validasi sukses, controller/service tetap memanggil `viewTab()`, `viewType()`, `exportTab()`, atau `export()` sesuai mode sebelum membaca data.

Tambahkan `after()` dengan aturan executable berikut:

- route index wajib mempunyai `tab` dan melarang `type`;
- route export wajib mempunyai tepat satu dari `tab` atau `type`;
- parameter `q` hanya diteruskan pada mode tab; filter khusus legacy hanya diteruskan pada mode `type`;
- `classroom_id` harus berada pada `academic_year_id` terpilih dan termasuk pilihan kelas actor;
- pada mode tab, `counselor_id` hanya sah untuk Koordinator pada tab `layanan`, harus menunjuk akun aktif ber-role aktif `guru_bk`, dan dilarang pada tab atau role lain; mode legacy mempertahankan perilaku `ReportRequest` existing agar bookmark ekspor lama tidak berubah.

Ubah `ReportRequest` agar tetap khusus `/reports/preview`: `type` wajib, `tab` prohibited, dan hapus `prepareForValidation()` yang memberi default tipe diam-diam:

```php
public function rules(): array
{
    return [
        'type' => ['required', Rule::in(ReportService::types())],
        'tab' => ['prohibited'],
        'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
        'date_start' => ['nullable', 'date'],
        'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
        'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        'student_id' => ['nullable', 'integer', 'exists:students,id'],
        'category' => ['nullable', 'string', 'max:100'],
        'service_field_id' => ['nullable', 'integer', 'exists:references,id'],
        'status_id' => ['nullable', 'integer', 'exists:references,id'],
        'achievement_type_id' => ['nullable', 'integer', 'exists:references,id'],
        'achievement_level_id' => ['nullable', 'integer', 'exists:references,id'],
        'counselor_id' => ['nullable', 'integer', 'exists:users,id'],
        'minimum_points' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        'format' => ['prohibited'],
        'page' => ['nullable', 'integer', 'min:1'],
    ];
}
```

Dengan demikian mode tab dan legacy tidak dapat tercampur pada index, preview, maupun export.

Tambahkan pesan Bahasa Indonesia untuk `tab`, `type`, `q`, tanggal, tahun ajaran, kelas, Guru BK, format, dan konflik mode. Error kelas/Guru BK forged cukup berbunyi `Kelas tidak tersedia untuk laporan ini.` atau `Guru BK tidak tersedia untuk laporan ini.` tanpa menyebut ID input.
Override redirect di atas mencegah loop validasi GET: index/export tab invalid kembali ke URL tab yang sudah disanitasi, sedangkan export legacy kembali ke preview type yang sah.

Hubungkan request ke index tanpa mengganti UI pada task ini:

```php
public function index(OperationalReportRequest $request, ReportService $service): View
{
    /** @var User $user */
    $user = $request->user();

    return view('pages.reports.index', ['reports' => $service->catalogFor($user)]);
}
```

Task 6 mengganti payload view dari katalog legacy menjadi hasil `OperationalReportRecapService`; perubahan kecil di sini hanya memastikan authorization dan validasi tab sudah aktif serta teruji sejak fondasi.

- [x] **Step 4: Tambahkan policy tab**

```php
public function viewTab(User $user, string $tab): bool
{
    return $this->viewAny($user)
        && in_array($tab, OperationalReportRecapService::tabs(), true);
}

public function exportTab(User $user, string $tab): bool
{
    return $this->viewTab($user, $tab);
}
```

- [x] **Step 5: Buat service skeleton dan helper aman**

Buat interface sejak fondasi agar Task 3-5 dapat menguji public dispatch tanpa menunggu integrasi UI:

```php
interface OperationalReportRecap
{
    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function build(User $actor, array $filters): array;

    /** @param array<string, mixed> $filters @return array{id: string, columns: list<string>, rows: iterable<int, array<string, mixed>>} */
    public function exportRows(User $actor, array $filters): array;
}
```

```php
final class OperationalReportRecapService implements OperationalReportRecap
{
    public const string TAB_VIOLATIONS = 'pelanggaran';
    public const string TAB_SERVICES = 'layanan';
    public const string TAB_ACHIEVEMENTS = 'prestasi';

    public static function tabs(): array
    {
        return [self::TAB_VIOLATIONS, self::TAB_SERVICES, self::TAB_ACHIEVEMENTS];
    }

    public function __construct(private readonly ReportPolicy $policy) {}

    public function build(User $actor, array $filters): array
    {
        abort_unless($this->policy->viewTab($actor, (string) $filters['tab']), 403);

        return match ($filters['tab']) {
            self::TAB_VIOLATIONS => $this->buildViolations($actor, $filters),
            self::TAB_SERVICES => $this->buildServices($actor, $filters),
            self::TAB_ACHIEVEMENTS => $this->buildAchievements($actor, $filters),
        };
    }

    public function exportRows(User $actor, array $filters): array
    {
        abort_unless($this->policy->exportTab($actor, (string) $filters['tab']), 403);

        return match ($filters['tab']) {
            self::TAB_VIOLATIONS => $this->exportViolations($actor, $filters),
            self::TAB_SERVICES => $this->exportServices($actor, $filters),
            self::TAB_ACHIEVEMENTS => $this->exportAchievements($actor, $filters),
        };
    }

    private function initials(?string $name): string
    {
        if (blank($name)) {
            return '—';
        }

        return collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->filter()
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)).'.')
            ->join('');
    }

    private function maskNisn(string $nisn): string
    {
        $length = mb_strlen($nisn);

        return $length <= 4
            ? str_repeat('*', $length)
            : mb_substr($nisn, 0, 2).str_repeat('*', $length - 4).mb_substr($nisn, -2);
    }

    /** @return array{AcademicYear|null, CarbonImmutable, CarbonImmutable} */
    private function period(array $filters): array
    {
        $year = isset($filters['academic_year_id'])
            ? AcademicYear::query()->find($filters['academic_year_id'])
            : AcademicYear::query()->active()->orderByDesc('starts_on')->first();
        $year ??= AcademicYear::query()->orderByDesc('starts_on')->first();
        $start = CarbonImmutable::parse($filters['date_start'] ?? $year?->starts_on?->toDateString() ?? now()->startOfYear()->toDateString());
        $end = CarbonImmutable::parse($filters['date_end'] ?? $year?->ends_on?->toDateString() ?? now()->endOfYear()->toDateString());

        return [$year, $start->startOfDay(), $end->endOfDay()];
    }

    /** @return Builder<Student> */
    private function accessibleStudents(User $actor): Builder
    {
        return Student::query()->accessibleTo($actor);
    }

    /** @return Builder<Classroom> */
    private function accessibleClassrooms(User $actor, ?AcademicYear $year): Builder
    {
        return Classroom::query()
            ->when($year, fn (Builder $query, AcademicYear $selected): Builder => $query
                ->where('academic_year_id', $selected->id))
            ->whereHas('studentClassMemberships.student', fn (Builder $students): Builder => $students
                ->accessibleTo($actor));
    }

    /** @return Builder<User> */
    private function activeCounselors(): Builder
    {
        return User::query()->active()->whereHas('roles', fn (Builder $roles): Builder => $roles
            ->where('slug', 'guru_bk')->where('is_active', true));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** @param list<string> $membershipPaths */
    private function whereHistoricClass(
        Builder $query,
        string $dateColumn,
        int $classroomId,
        ?AcademicYear $year,
        array $membershipPaths = ['student.classMemberships'],
    ): Builder {
        return $query->where(function (Builder $identities) use ($dateColumn, $classroomId, $year, $membershipPaths): void {
            foreach ($membershipPaths as $index => $path) {
                $method = $index === 0 ? 'whereHas' : 'orWhereHas';
                $identities->{$method}($path, function (Builder $memberships) use ($dateColumn, $classroomId, $year): void {
                    $memberships->where('classroom_id', $classroomId)
                        ->when($year, fn (Builder $scope, AcademicYear $selected): Builder => $scope
                            ->where('academic_year_id', $selected->id))
                        ->whereColumn('effective_from', '<=', $dateColumn)
                        ->where(function (Builder $period) use ($dateColumn): void {
                            $period->whereNull('effective_until')
                                ->orWhereColumn('effective_until', '>=', $dateColumn);
                        });
                });
            }
        });
    }
}
```

`accessibleStudents()` memakai scope model existing untuk cabang Koordinator dan Guru BK. `accessibleClassrooms()` berasal dari kelas tahun terpilih yang mempunyai membership murid dalam scope actor. `activeCounselors()` hanya memuat akun aktif dengan role aktif `guru_bk`. `period()` mempertahankan default `ReportService` existing. Semua pemakaian `escapeLike()` harus memakai binding dan klausa SQL `LIKE ? ESCAPE '\\'` yang kompatibel dengan SQLite dan MySQL; jangan menginterpolasi nilai pencarian ke raw SQL.
Import `App\Contracts\OperationalReportRecap`, `App\Policies\ReportPolicy`, model yang disebut helper, `Carbon\CarbonImmutable`, dan builder Eloquent. Policy diperiksa lagi pada public `build()`/`exportRows()` sebelum query dibuat.

- [x] **Step 6: Jalankan focused tests**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php --filter="authorization|request"
php vendor/bin/pint --test app/Contracts/OperationalReportRecap.php app/Http/Controllers/ReportController.php app/Http/Requests/OperationalReportRequest.php app/Http/Requests/ReportRequest.php app/Services/OperationalReportRecapService.php app/Policies/ReportPolicy.php tests/Feature/OperationalReportRecapTest.php
```

- [x] **Step 7: Commit**

```powershell
git add app/Contracts/OperationalReportRecap.php app/Http/Controllers/ReportController.php app/Http/Requests/OperationalReportRequest.php app/Http/Requests/ReportRequest.php app/Services/OperationalReportRecapService.php app/Policies/ReportPolicy.php tests/Feature/OperationalReportRecapTest.php
git commit -m "feat: siapkan fondasi rekap laporan operasional"
```

---

### Task 3: Implementasikan Rekap Pelanggaran dan Poin

**Files:**
- Modify: `app/Services/OperationalReportRecapService.php`
- Modify: `app/Models/Student.php`
- Extend: `tests/Feature/OperationalReportRecapTest.php`

**Interfaces:**
- Consumes: `accessibleStudents()`, periode ternormalisasi, `ExternalTatibRecord::active()`.
- Produces: `buildViolations(User $actor, array $filters): array`, `exportViolations(User $actor, array $filters): array`, dan row aman tab Pelanggaran.

- [x] **Step 1: Tulis failing tests agregasi**

```php
public function test_violation_tab_groups_searches_filters_and_keeps_scope(): void
{
    $teacherA = $this->userWithRole('guru_bk', 'Guru A');
    $teacherB = $this->userWithRole('guru_bk', 'Guru B');
    [$studentA] = $this->scopedStudent($teacherA, 'Murid Alpha Rahasia', '0012345678', 'X RPL 1');
    [$studentB] = $this->scopedStudent($teacherB, 'Murid Beta Rahasia', '0098765432', 'X RPL 2');
    $historicalClass = Classroom::query()->create([
        'academic_year_id' => $this->year->id, 'name' => 'X RPL Historis', 'is_active' => true,
    ]);
    StudentClassMembership::query()->where('student_id', $studentA->id)->update(['effective_from' => '2026-08-03']);
    StudentClassMembership::query()->create([
        'student_id' => $studentA->id, 'classroom_id' => $historicalClass->id,
        'academic_year_id' => $this->year->id, 'effective_from' => '2026-07-01',
        'effective_until' => '2026-08-02', 'is_active' => true,
    ]);
    $this->etatib($studentA, 'ET-A-1', 'Terlambat', 5, '2026-08-01');
    $this->etatib($studentA, 'ET-A-2', 'Atribut', 7, '2026-08-02');
    $this->etatib($studentB, 'ET-B-1', 'Guru lain', 9, '2026-08-03');

    $report = app(OperationalReportRecapService::class)->build($teacherA, [
        'tab' => 'pelanggaran', 'academic_year_id' => $this->year->id,
        'q' => 'Alpha', 'classroom_id' => $historicalClass->id,
    ]);
    $rows = collect($report['rows']->items());

    $this->assertCount(1, $rows);
    $this->assertSame(2, $rows->first()['violation_count']);
    $this->assertSame(12, $rows->first()['total_points']);
    $this->assertSame('Atribut', $rows->first()['latest_violation']);
    $this->assertSame('X RPL Historis', $rows->first()['classroom']);
    $this->assertSame('M.A.R.', $rows->first()['initials']);
    $this->assertStringNotContainsString($studentA->name, json_encode($rows->all(), JSON_THROW_ON_ERROR));

    $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
    $combined = app(OperationalReportRecapService::class)->build($coordinator, ['tab' => 'pelanggaran']);
    $this->assertSame(2, $combined['rows']->total());
}

public function test_coordinator_keeps_unlinked_etatib_identity_without_exposing_source_data(): void
{
    $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
    $teacher = $this->userWithRole('guru_bk', 'Guru');
    foreach (['ET-UNLINKED-1', 'ET-UNLINKED-2'] as $index => $identifier) {
        ExternalTatibRecord::query()->create([
            'source_identifier' => $identifier, 'nisn' => '0088888888',
            'occurred_at' => '2026-08-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).' 07:00:00',
            'violation_type' => '=SUM(1+1)', 'category' => 'Kedisiplinan', 'points' => 4,
            'is_active' => true, 'synced_at' => now(),
        ]);
    }

    $row = collect(app(OperationalReportRecapService::class)
        ->build($coordinator, ['tab' => 'pelanggaran'])['rows']->items())->first();
    $encoded = json_encode($row, JSON_THROW_ON_ERROR);

    $this->assertSame('Belum tertaut', $row['initials']);
    $this->assertSame('00******88', $row['masked_nisn']);
    $this->assertSame(2, $row['violation_count']);
    $this->assertStringNotContainsString('ET-UNLINKED', $encoded);
    $this->assertSame(0, app(OperationalReportRecapService::class)
        ->build($teacher, ['tab' => 'pelanggaran'])['rows']->total());
}

public function test_violation_query_count_does_not_grow_with_page_rows(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru Query');
    $classroom = Classroom::query()->create([
        'academic_year_id' => $this->year->id, 'name' => 'X Query', 'is_active' => true,
    ]);
    TeacherAssignment::query()->create([
        'user_id' => $teacher->id, 'classroom_id' => $classroom->id,
        'academic_year_id' => $this->year->id, 'effective_from' => '2026-07-01',
        'decision_number' => 'SK-QUERY', 'assigned_by' => $teacher->id,
    ]);
    $this->studentWithViolation($classroom, 1);

    DB::flushQueryLog();
    DB::enableQueryLog();
    app(OperationalReportRecapService::class)->build($teacher, ['tab' => 'pelanggaran']);
    $singleCount = count(DB::getQueryLog());

    foreach (range(2, 25) as $index) {
        $this->studentWithViolation($classroom, $index);
    }
    DB::flushQueryLog();
    $report = app(OperationalReportRecapService::class)->build($teacher, ['tab' => 'pelanggaran']);
    $manyCount = count(DB::getQueryLog());

    $this->assertCount(20, $report['rows']->items());
    $this->assertSame(25, $report['rows']->total());
    $this->assertLessThanOrEqual($singleCount + 2, $manyCount);
}
```

Fixture minimum: dua murid berbeda kelas, tiga record untuk murid A, satu record untuk murid B, membership historis sebelum pergantian kelas, dan dua record belum tertaut dengan NISN exact yang sama. Buktikan record belum tertaut hanya terlihat oleh Koordinator, digabung dengan kunci NISN exact sumber, tidak memperoleh nama/kelas rekaan, dan tidak pernah digabung berdasarkan teks nama.

Tambahkan helper fixture berikut pada test class; `studentWithViolation()` membuat `StudentClassMembership` pada `$this->year` dan memanggil `etatib()`:

```php
private function etatib(Student $student, string $identifier, string $violation, int $points, string $date): ExternalTatibRecord
{
    return ExternalTatibRecord::query()->create([
        'source_identifier' => $identifier, 'nisn' => $student->nisn, 'student_id' => $student->id,
        'occurred_at' => $date.' 07:00:00', 'violation_type' => $violation,
        'category' => 'Kedisiplinan', 'points' => $points, 'is_active' => true, 'synced_at' => now(),
    ]);
}

private function studentWithViolation(Classroom $classroom, int $index): Student
{
    $student = Student::query()->create([
        'nisn' => str_pad((string) $index, 10, '0', STR_PAD_LEFT),
        'name' => 'Murid Query '.$index, 'is_active' => true,
    ]);
    StudentClassMembership::query()->create([
        'student_id' => $student->id, 'classroom_id' => $classroom->id,
        'academic_year_id' => $this->year->id, 'effective_from' => '2026-07-01', 'is_active' => true,
    ]);
    $this->etatib($student, 'ET-QUERY-'.$index, 'Pelanggaran '.$index, 1, '2026-08-10');

    return $student;
}
```

- [x] **Step 2: Jalankan test dan pastikan gagal**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php --filter=violation
```

Expected: FAIL karena `buildViolations()` belum tersedia.

- [x] **Step 3: Tambahkan relasi e-Tatib pada Student**

```php
/** @return HasMany<ExternalTatibRecord, $this> */
public function etatibRecords(): HasMany
{
    return $this->hasMany(ExternalTatibRecord::class);
}
```

- [x] **Step 4: Implementasikan aggregate query**

Bangun dua aggregate query dari `ExternalTatibRecord`, bukan memuat record lalu mengelompokkan di Blade:

```php
$linked = ExternalTatibRecord::query()
    ->active()
    ->whereNotNull('student_id')
    ->whereBetween('occurred_at', [$start, $end])
    ->whereIn('student_id', $this->accessibleStudents($actor)->select('students.id'))
    ->when($filters['q'] ?? null, fn (Builder $records, string $q): Builder =>
        $records->whereHas('student', fn (Builder $students): Builder =>
            $students->where('name', 'like', '%'.$this->escapeLike($q).'%')))
    ->when($filters['classroom_id'] ?? null, fn (Builder $records, int $classroomId): Builder =>
        $this->whereHistoricClass($records, 'external_tatib_records.occurred_at', $classroomId, $year))
    ->selectRaw("'student' AS identity_type, student_id AS identity_value")
    ->selectRaw('MIN(id) AS identity_anchor_id, COUNT(*) AS violation_count, COALESCE(SUM(points), 0) AS total_points, MAX(occurred_at) AS latest_at')
    ->groupBy('student_id');

$includeUnlinked = $actor->hasRole('koordinator_bk')
    && blank($filters['q'] ?? null)
    && ! isset($filters['classroom_id']);

$unlinked = ExternalTatibRecord::query()
    ->active()
    ->whereNull('student_id')
    ->whereBetween('occurred_at', [$start, $end])
    ->when(! $includeUnlinked, fn (Builder $records): Builder => $records->whereRaw('1 = 0'))
    ->selectRaw("'etatib' AS identity_type, nisn AS identity_value")
    ->selectRaw('MIN(id) AS identity_anchor_id, COUNT(*) AS violation_count, COALESCE(SUM(points), 0) AS total_points, MAX(occurred_at) AS latest_at')
    ->groupBy('nisn');

$query = DB::query()
    ->fromSub($linked->unionAll($unlinked), 'violation_identities')
    ->orderByDesc('latest_at')
    ->orderBy('identity_type')
    ->orderBy('identity_value');
```

Derived `UNION ALL` ini menghindari cast identity lintas SQLite/MySQL. Paginate query aggregate dengan `paginate(20)->withQueryString()`. Ambil identitas, latest record dengan tie-breaker `occurred_at DESC, id DESC`, dan membership historis hanya untuk identity pada halaman menggunakan query batch konstan; jangan membuat query per row.

- [x] **Step 5: Bentuk kontrak output aman**

```php
[
    'identity_key' => 'student:'.$student->id,
    'initials' => $this->initials($student->name),
    'masked_nisn' => $this->maskNisn($student->nisn),
    'classroom' => $historicalClass,
    'violation_count' => (int) $aggregate->violation_count,
    'total_points' => (int) $aggregate->total_points,
    'latest_violation' => $latest->violation_type,
    'latest_date' => $latest->occurred_at->locale('id')->translatedFormat('d M Y'),
]
```

Untuk identitas e-Tatib belum tertaut gunakan key presentasi `etatib:{$aggregate->identity_anchor_id}` dari `MIN(external_tatib_records.id)` pada grup NISN exact, tampilkan `Belum tertaut` sebagai inisial/label, NISN tersamarkan, kelas `Belum tersedia`, dan jangan mengirim NISN mentah ke HTML/CSV. Anchor hanya menjadi key presentasi internal dan bukan tautan detail atau bukti identitas resmi.

Jangan sertakan `source_identifier`, raw provider body, nama lengkap, atau NISN asli.
Hitung ringkasan dari clone derived aggregate sebelum pagination: jumlah identitas, `SUM(violation_count)`, dan `SUM(total_points)`. Jangan menjumlah hanya 20 row pada halaman aktif.

`exportViolations()` memakai derived aggregate dan row mapper yang sama, mengganti paginator dengan `cursor()`/`LazyCollection` berurutan stabil, lalu mengembalikan:

```php
[
    'id' => self::TAB_VIOLATIONS,
    'columns' => ['Murid', 'NISN Tersamarkan', 'Kelas', 'Jumlah pelanggaran', 'Total poin', 'Pelanggaran terakhir'],
    'rows' => $rows,
]
```

- [x] **Step 6: Verifikasi**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php --filter=violation
php artisan test tests/Feature/ReportManagementTest.php --filter="teacher_scope|special_case|query_count"
php vendor/bin/pint --test app/Models/Student.php app/Services/OperationalReportRecapService.php tests/Feature/OperationalReportRecapTest.php
```

- [x] **Step 7: Commit**

```powershell
git add app/Models/Student.php app/Services/OperationalReportRecapService.php tests/Feature/OperationalReportRecapTest.php
git commit -m "feat: rekap pelanggaran dan poin per murid"
```

---

### Task 4: Implementasikan Rekap Layanan BK

**Files:**
- Modify: `app/Services/OperationalReportRecapService.php`
- Extend: `tests/Feature/OperationalReportRecapTest.php`

**Interfaces:**
- Consumes: `BkCase::accessibleTo()`, `Consultation::accessibleTo()`, `FollowUp`, `CaseAssignment::TYPE_OWNER`, dan reference category `follow_up_status`.
- Produces: `buildServices(User $actor, array $filters): array` dan `exportServices(User $actor, array $filters): array` termasuk identitas sementara.

- [x] **Step 1: Tulis failing tests layanan**

```php
public function test_service_tab_groups_official_reconciled_and_temporary_identities_safely(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru Layanan');
    [$student, $classroom] = $this->scopedStudent($teacher, 'Murid Layanan Resmi', '0022222222', 'XI RPL 1');
    $reconciled = $this->temporaryStudent('0022222222', 'Nama Masukan Lama', $teacher, $student);
    $unreconciled = $this->temporaryStudent('0033333333', 'Murid Sementara', $teacher);
    $officialCase = $this->caseRecord($teacher, $student, null, '2026-08-10', 'RAHASIA-KASUS');
    $temporaryCase = $this->caseRecord($teacher, null, $unreconciled, '2026-08-11', 'RAHASIA-SEMENTARA');
    $this->consultationRecord($teacher, null, $reconciled, '2026-08-18', 'RAHASIA-KONSULTASI');
    $this->followUpRecord($officialCase, $teacher, 'terlaksana', '2026-08-15', '2026-08-19');
    $this->followUpRecord($officialCase, $teacher, 'terjadwal', '2026-08-25');

    $report = app(OperationalReportRecapService::class)->build($teacher, ['tab' => 'layanan']);
    $rows = collect($report['rows']->items())->keyBy('identity_key');
    $official = $rows->get('student:'.$student->id);
    $temporary = $rows->get('temporary:'.$unreconciled->id);

    $this->assertCount(2, $rows);
    $this->assertSame(1, $official['case_count']);
    $this->assertSame(1, $official['consultation_count']);
    $this->assertSame(2, $official['follow_up_count']);
    $this->assertSame(1, $official['open_follow_up_count']);
    $this->assertSame('19 Agu 2026', $official['latest_service_date']);
    $this->assertSame($classroom->name, $official['classroom']);
    $this->assertSame('Belum tersedia', $temporary['classroom']);
    $this->assertTrue($temporary['is_temporary']);
    $this->assertStringNotContainsString('RAHASIA-', json_encode($rows->all(), JSON_THROW_ON_ERROR));

    $filtered = app(OperationalReportRecapService::class)->build($teacher, [
        'tab' => 'layanan', 'q' => 'Resmi', 'classroom_id' => $classroom->id,
    ]);
    $this->assertSame(1, $filtered['rows']->total());
    $this->assertSame('student:'.$student->id, $filtered['rows']->items()[0]['identity_key']);
    $this->assertNotNull($temporaryCase->id);
}

public function test_coordinator_counselor_filter_uses_effective_owner_not_recorder(): void
{
    $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator');
    $ownerA = $this->userWithRole('guru_bk', 'Guru A');
    $ownerB = $this->userWithRole('guru_bk', 'Guru B');
    [$studentA] = $this->scopedStudent($ownerA, 'Murid Owner A', '0044444444', 'X AKL 1');
    [$studentB] = $this->scopedStudent($ownerB, 'Murid Owner B', '0055555555', 'X AKL 2');
    $caseA = $this->caseRecord($ownerA, $studentA, null, '2026-08-10', 'Kasus A');
    $caseB = $this->caseRecord($ownerB, $studentB, null, '2026-08-10', 'Kasus B');
    $caseA->update(['created_by' => $ownerB->id]);
    $this->followUpRecord($caseA, $ownerB, 'terjadwal', '2026-08-22');
    $this->followUpRecord($caseB, $ownerA, 'terjadwal', '2026-08-22');
    $this->consultationRecord($ownerA, $studentA, null, '2026-08-12', 'Konsultasi A');
    $this->consultationRecord($ownerB, $studentB, null, '2026-08-12', 'Konsultasi B');

    $rows = collect(app(OperationalReportRecapService::class)->build($coordinator, [
        'tab' => 'layanan', 'counselor_id' => $ownerA->id,
    ])['rows']->items());

    $this->assertCount(1, $rows);
    $this->assertSame('student:'.$studentA->id, $rows->first()['identity_key']);
    $this->assertSame(1, $rows->first()['case_count']);
    $this->assertSame(1, $rows->first()['consultation_count']);
    $this->assertSame(1, $rows->first()['follow_up_count']);
}

public function test_service_identity_query_is_paginated_before_batch_hydration(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru Query Layanan');
    $classroom = Classroom::query()->create([
        'academic_year_id' => $this->year->id, 'name' => 'X Layanan', 'is_active' => true,
    ]);
    TeacherAssignment::query()->create([
        'user_id' => $teacher->id, 'classroom_id' => $classroom->id,
        'academic_year_id' => $this->year->id, 'effective_from' => '2026-07-01',
        'decision_number' => 'SK-LAYANAN', 'assigned_by' => $teacher->id,
    ]);
    $this->studentWithCase($teacher, $classroom, 1);

    DB::flushQueryLog();
    DB::enableQueryLog();
    app(OperationalReportRecapService::class)->build($teacher, ['tab' => 'layanan']);
    $singleCount = count(DB::getQueryLog());
    foreach (range(2, 25) as $index) {
        $this->studentWithCase($teacher, $classroom, $index);
    }
    DB::flushQueryLog();
    $report = app(OperationalReportRecapService::class)->build($teacher, ['tab' => 'layanan']);

    $this->assertSame(25, $report['rows']->total());
    $this->assertCount(20, $report['rows']->items());
    $this->assertLessThanOrEqual($singleCount + 3, count(DB::getQueryLog()));
}
```

Test identitas sementara harus membuktikan dua nama mirip tidak digabung dan key memakai `temporary_students.id`. Test rekonsiliasi harus membuktikan record yang masih menunjuk `temporary_student_id`, tetapi `temporary_students.reconciled_student_id` sudah terisi, masuk ke row `student:{id}` yang sama dengan record resmi.

Tambahkan helper fixture konkret:

```php
private function temporaryStudent(string $nisn, string $name, User $creator, ?Student $reconciled = null): TemporaryStudent
{
    return TemporaryStudent::query()->create([
        'nisn' => $nisn, 'input_name' => $name, 'created_by' => $creator->id,
        'reconciliation_status_id' => $this->reference(
            'reconciliation_status', $reconciled === null ? 'menunggu_rekonsiliasi' : 'terekonsiliasi',
        )->id,
        'reconciled_student_id' => $reconciled?->id,
        'reconciled_by' => $reconciled === null ? null : $creator->id,
        'reconciled_at' => $reconciled === null ? null : now(),
    ]);
}

private function caseRecord(User $owner, ?Student $student, ?TemporaryStudent $temporary, string $date, string $secret): BkCase
{
    $case = BkCase::query()->create([
        'student_id' => $student?->id, 'temporary_student_id' => $temporary?->id,
        'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
        'service_field_id' => $this->reference('service_field', 'pribadi')->id,
        'status_id' => $this->reference('case_status', ServiceRecordStatus::NEW)->id,
        'service_date' => $date, 'initial_info' => $secret,
        'initial_action' => 'Asesmen awal', 'created_by' => $owner->id,
    ]);
    CaseAssignment::query()->create([
        'case_id' => $case->id, 'user_id' => $owner->id,
        'assignment_type' => CaseAssignment::TYPE_OWNER, 'effective_from' => $date,
        'reason' => 'Penanggung jawab test', 'assigned_by' => $owner->id,
    ]);

    return $case;
}

private function consultationRecord(User $counselor, ?Student $student, ?TemporaryStudent $temporary, string $date, string $secret): Consultation
{
    return Consultation::query()->create([
        'student_id' => $student?->id, 'temporary_student_id' => $temporary?->id,
        'service_field_id' => $this->reference('service_field', 'pribadi')->id,
        'status_id' => $this->reference('consultation_status', ServiceRecordStatus::COMPLETED)->id,
        'topic' => 'Topik aman', 'session_date' => $date,
        'general_summary' => $secret, 'counselor_id' => $counselor->id,
    ]);
}

private function followUpRecord(BkCase $case, User $recorder, string $status, string $planned, ?string $executed = null): FollowUp
{
    return FollowUp::query()->create([
        'case_id' => $case->id,
        'follow_up_type_id' => $this->reference('follow_up_type', 'konsultasi_individual')->id,
        'status_id' => $this->reference('follow_up_status', $status)->id,
        'planned_date' => $planned, 'execution_date' => $executed,
        'result' => 'RAHASIA-HASIL', 'next_plan' => 'RAHASIA-RENCANA',
        'recorded_by' => $recorder->id,
    ]);
}

private function studentWithCase(User $teacher, Classroom $classroom, int $index): Student
{
    $student = Student::query()->create([
        'nisn' => '1'.str_pad((string) $index, 9, '0', STR_PAD_LEFT),
        'name' => 'Murid Layanan '.$index, 'is_active' => true,
    ]);
    StudentClassMembership::query()->create([
        'student_id' => $student->id, 'classroom_id' => $classroom->id,
        'academic_year_id' => $this->year->id, 'effective_from' => '2026-07-01', 'is_active' => true,
    ]);
    $this->caseRecord($teacher, $student, null, '2026-08-10', 'Fixture');

    return $student;
}
```

- [x] **Step 2: Jalankan test dan pastikan gagal**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php --filter=service_tab
```

- [x] **Step 3: Agregasikan tiga sumber secara terpisah**

Gunakan query grouped per `student_id`/`temporary_student_id` untuk kasus dan konsultasi, serta identitas milik kasus untuk tindak lanjut:

```php
$caseQuery = BkCase::query()->accessibleTo($actor)
    ->whereBetween('service_date', [$start->toDateString(), $end->toDateString()]);

$consultationQuery = Consultation::query()->accessibleTo($actor)
    ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()]);

$followUpQuery = FollowUp::query()
    ->whereHas('case', fn (Builder $cases): Builder => $cases->accessibleTo($actor))
    ->whereBetween(DB::raw('COALESCE(execution_date, planned_date)'), [$start->toDateString(), $end->toDateString()]);
```

Normalisasikan setiap sumber menjadi kolom `identity_type`, `identity_id`, `included_at`, `actual_at`, `case_count`, `consultation_count`, `follow_up_count`, dan `open_follow_up_count`. Untuk kasus/konsultasi, `actual_at` sama dengan tanggal layanan. Untuk tindak lanjut, `included_at = COALESCE(execution_date, planned_date)` dan `actual_at = execution_date`. Join `temporary_students` agar identitas yang sudah direkonsiliasi memakai `reconciled_student_id`; hanya identitas yang benar-benar belum direkonsiliasi memakai `temporary_students.id`.

Terapkan `q` pada ketiga sumber sebelum union: nama `students.name` untuk identitas resmi/direct, nama murid hasil `temporary_students.reconciled_student_id` untuk identitas yang sudah direkonsiliasi, dan `temporary_students.input_name` hanya untuk identitas yang belum direkonsiliasi. Terapkan filter kelas dengan membership efektif pada tanggal masing-masing record; identitas sementara yang belum direkonsiliasi tidak lolos ketika `classroom_id` aktif.

Filter Koordinator memakai penanggung jawab kasus yang efektif pada tanggal record untuk kasus dan tindak lanjut, serta `consultations.counselor_id` untuk konsultasi. Jangan memakai `cases.created_by` atau `follow_ups.recorded_by` sebagai owner karena keduanya hanya menyatakan pencatat. Untuk kasus gunakan assignment `owner` yang periodenya mencakup `cases.service_date`; untuk tindak lanjut gunakan assignment `owner` yang periodenya mencakup `COALESCE(follow_ups.execution_date, follow_ups.planned_date)`. Filter tersebut wajib diterapkan pada ketiga subquery sebelum union agar seluruh hitungan row benar-benar dibatasi ke Guru BK terpilih.

- [x] **Step 4: Gabungkan aggregate map dengan identity key stabil**

```php
private function identityKey(
    ?int $studentId,
    ?int $temporaryStudentId,
    ?int $reconciledStudentId,
): string
{
    $officialStudentId = $studentId ?? $reconciledStudentId;

    return $officialStudentId !== null
        ? 'student:'.$officialStudentId
        : 'temporary:'.(int) $temporaryStudentId;
}
```

Gabungkan tiga normalized query dengan `UNION ALL`, lalu group derived table berdasarkan `identity_type` dan `identity_id`:

```php
$identityQuery = DB::query()
    ->fromSub($caseEvents->unionAll($consultationEvents)->unionAll($followUpEvents), 'service_events')
    ->select(['identity_type', 'identity_id'])
    ->selectRaw('SUM(case_count) AS case_count')
    ->selectRaw('SUM(consultation_count) AS consultation_count')
    ->selectRaw('SUM(follow_up_count) AS follow_up_count')
    ->selectRaw('SUM(open_follow_up_count) AS open_follow_up_count')
    ->selectRaw('MAX(included_at) AS latest_included_at')
    ->selectRaw('MAX(actual_at) AS latest_service_at')
    ->groupBy('identity_type', 'identity_id')
    ->orderByDesc('latest_included_at')
    ->orderBy('identity_type')
    ->orderBy('identity_id');

$page = (clone $identityQuery)->paginate(20)->withQueryString();
```

Setiap case event mengisi flag `1,0,0,0`; consultation `0,1,0,0`; follow-up `0,0,1,CASE WHEN follow_up_status.code IN ('terjadwal', 'ditunda') THEN 1 ELSE 0 END`. Dengan demikian hitungan berasal dari seluruh dataset sebelum pagination tanpa query per row. Setelah page diperoleh, hydrate Student/TemporaryStudent dan membership tanggal terakhir dalam query batch konstan. Status follow-up terbuka adalah `terjadwal` atau `ditunda`; `terlaksana` dan `dibatalkan` tidak dihitung terbuka.

- [x] **Step 5: Terapkan kelas dan layanan terakhir**

Kelas memakai membership efektif pada tanggal aktual terbaru. Untuk follow-up, `execution_date` boleh menjadi layanan aktual; `planned_date` hanya menentukan inklusi hitungan dan urutan row, tetapi tidak boleh menaikkan `latest_service_at`. Bila suatu row hanya mempunyai tindak lanjut terjadwal dan belum mempunyai aktivitas aktual, tampilkan layanan terakhir `Belum terlaksana` dan kelas `Belum tersedia`, bukan memakai tanggal rencana sebagai histori kelas. Identitas sementara yang belum direkonsiliasi memakai `Belum tersedia` dan badge `Belum terverifikasi Dapodik`.
Hitung ringkasan dari clone `$identityQuery` sebelum pagination: jumlah identitas, jumlah seluruh kasus+konsultasi+tindak lanjut, dan jumlah tindak lanjut terbuka.

`exportServices()` memakai `$identityQuery` dan mapper yang sama tanpa pagination, lalu mengembalikan ID `layanan`, kolom `Murid`, `NISN Tersamarkan`, `Kelas`, `Kasus`, `Konsultasi`, `Tindak lanjut`, `Perlu tindak lanjut`, dan `Layanan terakhir`, serta rows berupa `LazyCollection`/generator.

- [x] **Step 6: Verifikasi**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php --filter=service_tab
php artisan test tests/Feature/ReportManagementTest.php --filter="consultation|coordinator|multi_role"
php artisan test --filter CaseManagementTest
php artisan test --filter ConsultationManagementTest
php vendor/bin/pint --test app/Services/OperationalReportRecapService.php tests/Feature/OperationalReportRecapTest.php
```

- [x] **Step 7: Commit**

```powershell
git add app/Services/OperationalReportRecapService.php tests/Feature/OperationalReportRecapTest.php
git commit -m "feat: rekap layanan BK per murid"
```

---

### Task 5: Implementasikan Rekap Prestasi

**Files:**
- Modify: `app/Services/OperationalReportRecapService.php`
- Extend: `tests/Feature/OperationalReportRecapTest.php`

**Interfaces:**
- Consumes: `Achievement::accessibleTo()`, referensi `achievement_level`, dan `achievement_verification_status`.
- Produces: `buildAchievements(User $actor, array $filters): array` dan `exportAchievements(User $actor, array $filters): array`.

- [x] **Step 1: Tulis failing tests prestasi**

```php
public function test_achievement_tab_groups_uses_verified_sort_order_and_hides_private_fields(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru Prestasi');
    [$student, $classroom] = $this->scopedStudent($teacher, 'Murid Prestasi Rahasia', '0066666666', 'XI DKV 1');
    $this->achievementRecord($student, $teacher, 'Lomba Nasional', 'nasional', 'terverifikasi', '2026-08-10');
    $this->achievementRecord($student, $teacher, '=SUM(1+1)', 'internasional', 'menunggu', '2026-08-12');

    $report = app(OperationalReportRecapService::class)->build($teacher, [
        'tab' => 'prestasi', 'q' => 'Prestasi', 'classroom_id' => $classroom->id,
    ]);
    $row = $report['rows']->items()[0];
    $encoded = json_encode($row, JSON_THROW_ON_ERROR);

    $this->assertSame(2, $row['achievement_count']);
    $this->assertSame(1, $row['verified_count']);
    $this->assertSame('Nasional', $row['highest_verified_level']);
    $this->assertSame('=SUM(1+1)', $row['latest_achievement']);
    $this->assertStringNotContainsString('BUKTI-RAHASIA', $encoded);
    $this->assertStringNotContainsString('CATATAN-RAHASIA', $encoded);
    $this->assertStringNotContainsString($student->name, $encoded);
}

public function test_achievement_tab_keeps_scope_historical_class_and_stable_pagination(): void
{
    $teacherA = $this->userWithRole('guru_bk', 'Guru A');
    $teacherB = $this->userWithRole('guru_bk', 'Guru B');
    [$studentA] = $this->scopedStudent($teacherA, 'Prestasi A', '0077777777', 'XI AKL 1');
    [$studentB] = $this->scopedStudent($teacherB, 'Prestasi B', '0088888888', 'XI AKL 2');
    $historicalClass = Classroom::query()->create([
        'academic_year_id' => $this->year->id, 'name' => 'X AKL Historis', 'is_active' => true,
    ]);
    StudentClassMembership::query()->where('student_id', $studentA->id)->update(['effective_from' => '2026-08-11']);
    StudentClassMembership::query()->create([
        'student_id' => $studentA->id, 'classroom_id' => $historicalClass->id,
        'academic_year_id' => $this->year->id, 'effective_from' => '2026-07-01',
        'effective_until' => '2026-08-10', 'is_active' => true,
    ]);
    $this->achievementRecord($studentA, $teacherA, 'Prestasi A', 'sekolah', 'terverifikasi', '2026-08-10');
    $this->achievementRecord($studentB, $teacherB, 'Prestasi B', 'sekolah', 'terverifikasi', '2026-08-10');

    $report = app(OperationalReportRecapService::class)->build($teacherA, [
        'tab' => 'prestasi', 'classroom_id' => $historicalClass->id,
    ]);

    $this->assertSame(1, $report['rows']->total());
    $this->assertSame('student:'.$studentA->id, $report['rows']->items()[0]['identity_key']);
    $this->assertSame($historicalClass->name, $report['rows']->items()[0]['classroom']);
}
```

Fixture harus memuat prestasi nasional terverifikasi dan internasional menunggu; tingkat tertinggi yang tampil harus `Nasional`.

Tambahkan helper:

```php
private function achievementRecord(
    Student $student,
    User $recorder,
    string $activity,
    string $level,
    string $verification,
    string $date,
): Achievement {
    return Achievement::query()->create([
        'student_id' => $student->id,
        'type_id' => $this->reference('achievement_type', 'akademik')->id,
        'level_id' => $this->reference('achievement_level', $level)->id,
        'activity_name' => $activity, 'organizer' => 'Sekolah',
        'achievement_date' => $date, 'result' => 'Juara',
        'evidence_reference' => 'BUKTI-RAHASIA',
        'evidence_description' => 'DESKRIPSI-RAHASIA',
        'notes' => 'CATATAN-RAHASIA',
        'verification_status_id' => $this->reference('achievement_verification_status', $verification)->id,
        'recorded_by' => $recorder->id,
        'verification_notes' => 'VERIFIKASI-RAHASIA',
    ]);
}
```

- [x] **Step 2: Jalankan test dan pastikan gagal**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php --filter=achievement_tab
```

- [x] **Step 3: Implementasikan aggregate query**

```php
$query = Achievement::query()
    ->accessibleTo($actor)
    ->whereBetween('achievement_date', [$start->toDateString(), $end->toDateString()])
    ->when($filters['q'] ?? null, fn (Builder $items, string $q): Builder =>
        $items->whereHas('student', fn (Builder $students): Builder =>
            $students->where('name', 'like', '%'.$this->escapeLike($q).'%')));
```

Terapkan filter kelas melalui membership efektif pada `achievement_date`. Join tabel `references` dengan alias berbeda untuk level dan status verifikasi, batasi masing-masing alias ke category yang benar, lalu agregasikan jumlah total, jumlah terverifikasi, `MAX(achievement_date)`, dan `MAX(level_reference.sort_order)` hanya saat `verification_reference.code = 'terverifikasi'`. Query aggregate di-group `student_id`, diurutkan `latest_at DESC, student_id`, dan dipaginasi 20 row di database sebelum row dihidrasi.

Ambil prestasi terbaru dengan tie-breaker `achievement_date DESC, achievements.id DESC`. Ambil label tingkat tertinggi melalui pasangan category `achievement_level` dan `sort_order` hasil aggregate; jangan memilih `MAX(label)` atau menganggap ID referensi merepresentasikan tingkat.

- [x] **Step 4: Bentuk row aman**

```php
[
    'identity_key' => 'student:'.$student->id,
    'initials' => $this->initials($student->name),
    'masked_nisn' => $this->maskNisn($student->nisn),
    'classroom' => $historicalClass,
    'achievement_count' => (int) $aggregate->achievement_count,
    'verified_count' => (int) $aggregate->verified_count,
    'highest_verified_level' => $level?->label ?? 'Belum ada',
    'latest_achievement' => $latest->activity_name,
    'latest_date' => $latest->achievement_date->locale('id')->translatedFormat('d M Y'),
]
```

Jangan select atau expose `evidence_reference`, `evidence_description`, `notes`, atau `verification_notes`.
Hitung ringkasan dari clone aggregate sebelum pagination: jumlah murid, `SUM(achievement_count)`, dan `SUM(verified_count)`.

`exportAchievements()` memakai aggregate dan mapper yang sama tanpa pagination, lalu mengembalikan ID `prestasi`, kolom `Murid`, `NISN Tersamarkan`, `Kelas`, `Jumlah prestasi`, `Terverifikasi`, `Tingkat tertinggi`, dan `Prestasi terbaru`, serta rows berupa `LazyCollection`/generator. Kolom terbaru menggabungkan label aman dan tanggal seperti tabel aktif.

- [x] **Step 5: Verifikasi**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php --filter=achievement_tab
php artisan test tests/Feature/AchievementManagementTest.php
php vendor/bin/pint --test app/Services/OperationalReportRecapService.php tests/Feature/OperationalReportRecapTest.php
```

- [x] **Step 6: Commit**

```powershell
git add app/Services/OperationalReportRecapService.php tests/Feature/OperationalReportRecapTest.php
git commit -m "feat: rekap prestasi per murid"
```

---

### Task 6: Hubungkan Controller, Ekspor, dan UI Tiga Tab

**Files:**
- Modify: `app/Http/Controllers/ReportController.php`
- Modify: `app/Services/OperationalReportRecapService.php`
- Modify: `routes/web.php`
- Modify: `resources/views/pages/reports/index.blade.php`
- Create: `resources/views/pages/reports/_filters.blade.php`
- Create: `resources/views/pages/reports/_desktop-table.blade.php`
- Create: `resources/views/pages/reports/_mobile-cards.blade.php`
- Modify: `resources/scss/app-dashboard.scss`
- Modify: `scripts/check-frontend.mjs`
- Extend: `tests/Feature/OperationalReportRecapTest.php`
- Extend: `tests/Feature/ReportManagementTest.php`
- Extend: `tests/Feature/FrontendPreviewTest.php`
- Extend: `tests/Feature/AuthorizationMatrixTest.php`

**Interfaces:**
- Consumes: `OperationalReportRequest`, seluruh `build*()` tab, dan `ReportService` legacy.
- Produces: halaman `/reports` bertab, CSV tab aktif, serta kompatibilitas URL legacy.

- [x] **Step 1: Tulis failing HTTP dan view tests**

```php
public function test_reports_index_uses_three_deep_links_without_legacy_cards(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru UI');

    $this->actingAs($teacher)->get(route('reports.index'))
        ->assertOk()
        ->assertSee('Layanan BK')
        ->assertSee('aria-current="page"', false)
        ->assertSee(route('reports.index', ['tab' => 'pelanggaran']), false)
        ->assertSee(route('reports.index', ['tab' => 'prestasi']), false)
        ->assertDontSee('Pelanggaran per Murid')
        ->assertDontSee('Pelanggaran per Kelas')
        ->assertDontSee('Poin Pelanggaran')
        ->assertDontSee('sibk-report-grid');

    $coordinator = $this->userWithRole('koordinator_bk', 'Koordinator UI');
    $this->actingAs($teacher)->get(route('reports.index', ['tab' => 'layanan']))
        ->assertDontSee('name="counselor_id"', false);
    $this->actingAs($coordinator)->get(route('reports.index', ['tab' => 'layanan']))
        ->assertSee('name="counselor_id"', false);
    $this->actingAs($coordinator)->get(route('reports.index', ['tab' => 'prestasi']))
        ->assertDontSee('name="counselor_id"', false);
}

public function test_each_tab_renders_one_table_mobile_cards_and_preserves_valid_filters(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru Markup');
    [, $classroom] = $this->scopedStudent($teacher, 'Murid Markup', '0099999999', 'X Markup');

    foreach (['pelanggaran', 'layanan', 'prestasi'] as $tab) {
        $response = $this->actingAs($teacher)->get(route('reports.index', [
            'tab' => $tab, 'q' => 'Markup', 'academic_year_id' => $this->year->id,
            'classroom_id' => $classroom->id,
        ]));
        $response->assertOk()
            ->assertSee('name="tab" value="'.$tab.'"', false)
            ->assertSee('name="q"', false)
            ->assertSee('name="classroom_id"', false)
            ->assertSee('sibk-operational-report-table', false)
            ->assertSee('sibk-operational-report-cards', false)
            ->assertSee('data-print-report', false)
            ->assertSee('q=Markup', false)
            ->assertSee('classroom_id='.$classroom->id, false)
            ->assertDontSee('onclick=', false);
    }

    foreach (range(1, 21) as $index) {
        $this->studentWithViolation($classroom, 100 + $index);
    }
    $this->actingAs($teacher)->get(route('reports.index', [
        'tab' => 'pelanggaran', 'academic_year_id' => $this->year->id,
        'classroom_id' => $classroom->id,
    ]))->assertOk()->assertSee('page=2', false)->assertSee('tab=pelanggaran', false);
}

public function test_operational_csv_and_legacy_contract_use_their_own_validated_modes(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru CSV Baru');
    [$student] = $this->scopedStudent($teacher, 'Nama CSV Baru', '0010101010', 'X CSV');
    $this->etatib($student, 'ET-CSV-BARU', '=SUM(1+1)', 9, '2026-08-10');

    $operational = $this->actingAs($teacher)->get(route('reports.export', [
        'tab' => 'pelanggaran', 'format' => 'csv',
    ]));
    $operational->assertOk()->assertDownload()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $csv = $operational->streamedContent();
    $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    $this->assertStringContainsString('Jumlah pelanggaran', $csv);
    $this->assertStringContainsString('N.C.B.', $csv);
    $this->assertStringContainsString("'=SUM(1+1)", $csv);
    $this->assertStringNotContainsString($student->name, $csv);
    $this->assertStringNotContainsString($student->nisn, $csv);

    $this->actingAs($teacher)->get(route('reports.preview', [
        'type' => ReportService::TYPE_STUDENT_VIOLATIONS,
    ]))->assertOk();
    $this->actingAs($teacher)->get(route('reports.export', [
        'type' => ReportService::TYPE_STUDENT_VIOLATIONS, 'format' => 'csv',
    ]))->assertOk()->assertDownload();
    $this->actingAs($teacher)->get(route('reports.export', [
        'tab' => 'layanan', 'type' => ReportService::TYPE_SERVICE_RECAP, 'format' => 'csv',
    ]))->assertSessionHasErrors(['tab', 'type']);
    $this->actingAs($teacher)->get(route('reports.export', ['format' => 'csv']))
        ->assertSessionHasErrors(['tab', 'type']);
}

public function test_invalid_and_empty_states_are_accessible_and_reset_active_tab(): void
{
    $teacher = $this->userWithRole('guru_bk', 'Guru State');

    $invalid = $this->actingAs($teacher)->followingRedirects()->get(route('reports.index', [
        'tab' => 'prestasi', 'date_start' => '2026-08-20', 'date_end' => '2026-08-01',
    ]));
    $invalid->assertOk()
        ->assertSee('role="alert"', false)
        ->assertSee('tabindex="-1"', false)
        ->assertSee('Tanggal akhir tidak boleh sebelum tanggal awal.');

    $this->actingAs($teacher)->get(route('reports.index', ['tab' => 'prestasi', 'q' => 'Tidak Ada']))
        ->assertOk()
        ->assertSee('Tidak ada murid pada periode atau filter terpilih')
        ->assertSee(route('reports.index', ['tab' => 'prestasi']), false);
}
```

Tambahkan assertion `aria-current="page"`, label field, tombol Reset, `data-print-report`, dan ketiadaan `onclick`.

- [x] **Step 2: Jalankan test dan pastikan gagal**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php --filter="index|renders|filters|csv|legacy|mobile"
php artisan test tests/Feature/FrontendPreviewTest.php --filter=report
```

- [x] **Step 3: Hubungkan index ke service rekap**

Task 2 sudah membuat interface dan public dispatch, sedangkan Task 3-5 sudah menyediakan pasangan private builder `build*()` dan `export*()`. Pada task ini hubungkan controller ke service concrete tanpa binding container baru.

```php
public function index(
    OperationalReportRequest $request,
    OperationalReportRecapService $service,
): View {
    /** @var User $user */
    $user = $request->user();

    return view('pages.reports.index', [
        'report' => $service->build($user, $request->filters()),
    ]);
}
```

`build()` melakukan dispatch berdasarkan tab dan menambahkan daftar tab, filter options, paginator, columns, rows, period, generated_by, serta generated_at. `rows` selalu paginator hasil query database; service tidak boleh mengambil seluruh aggregate ke Collection sebelum pagination.

- [x] **Step 4: Pisahkan mode ekspor tab dan legacy**

Gunakan `OperationalReportRequest` pada route export. Request tersebut sudah menerima tepat satu dari `type` atau `tab`, sedangkan `ReportRequest` tetap hanya dipakai route preview legacy. Controller memilih pipeline secara eksplisit:

```php
$data = $request->filters();

if (array_key_exists('tab', $data)) {
    abort_unless($policy->exportTab($user, (string) $data['tab']), 403);
    $report = $recaps->exportRows($user, $data);
} else {
    abort_unless($policy->export($user, (string) $data['type']), 403);
    $report = $legacy->exportRows($user, $data);
}

$format = (string) $request->validated('format');
```

`OperationalReportRecapService::exportRows()` memakai generator/`LazyCollection` yang memproses identity aggregate secara chunk dengan urutan stabil; jangan memanggil `get()` atas seluruh hasil. Gunakan helper CSV existing untuk UTF-8 BOM dan formula-injection protection. Filename memakai allowlist `pelanggaran|layanan|prestasi` atau legacy type, bukan nilai query mentah.

- [x] **Step 5: Ganti katalog kartu dengan tab**

Struktur minimum `index.blade.php`:

```blade
<nav aria-label="Jenis laporan Guru BK dan Koordinator">
    <div class="nav sibk-report-tabs">
        @foreach($report['tabs'] as $key => $label)
            @php
                $tabQuery = array_filter([
                    'tab' => $key,
                    'q' => $report['filters']['q'] ?? null,
                    'academic_year_id' => $report['filters']['academic_year_id'] ?? null,
                    'date_start' => $report['filters']['date_start'] ?? null,
                    'date_end' => $report['filters']['date_end'] ?? null,
                    'classroom_id' => $report['filters']['classroom_id'] ?? null,
                    'counselor_id' => $key === 'layanan' ? ($report['filters']['counselor_id'] ?? null) : null,
                ], static fn ($value): bool => $value !== null && $value !== '');
            @endphp
            <a class="nav-link {{ $report['tab'] === $key ? 'active' : '' }}"
               href="{{ route('reports.index', $tabQuery) }}"
               @if($report['tab'] === $key) aria-current="page" @endif>
                {{ $label }}
            </a>
        @endforeach
    </div>
</nav>

@include('pages.reports._filters')
@include('pages.reports._desktop-table')
@include('pages.reports._mobile-cards')
```

Form filter memakai method GET, action `reports.index`, hidden `tab`, dan tidak mengirim `page`; perubahan filter otomatis kembali ke halaman pertama. Tombol Reset menuju `route('reports.index', ['tab' => $report['tab']])`. URL cetak/ekspor dibentuk hanya dari filter tervalidasi pada `$report['filters']`, bukan `request()->query()` mentah. Desktop dan mobile membaca row array yang sama. Jangan menyimpan nama model atau property sensitif pada `data-*` attribute.

Letakkan ringkasan validasi sebelum navigasi tab dengan `role="alert"`, heading yang dapat menerima fokus (`tabindex="-1"`), dan daftar pesan aman. Empty state memakai `<x-empty-state>`, menjelaskan bahwa tidak ada murid pada periode/filter terpilih, serta menampilkan Reset filter ke tab aktif bila filter non-default sedang digunakan.

Cetak dibatasi melalui container `data-print-report`: judul, konteks filter, waktu pembuatan, pembuat, ringkasan, dan tabel aktif tetap terlihat; navigasi, form filter, pagination, serta kartu mobile disembunyikan pada media print. JavaScript hanya memanggil `window.print()` dari tombol yang sudah ada dan tidak mengubah data.

- [x] **Step 6: Terapkan responsive dan aksesibilitas**

Generalisasi style tab tanpa memutus Waka:

```scss
.sibk-report-tabs,
.sibk-waka-tabs {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  scrollbar-width: thin;
}

.sibk-operational-report-cards { display: none; }

@media (max-width: 767.98px) {
  .sibk-operational-report-table { display: none; }
  .sibk-operational-report-cards { display: grid; gap: 1rem; }
}
```

Target tombol minimum 44 px, focus ring existing, heading berurutan, dan empty state memakai `<x-empty-state>`.
Tambahkan `@media print` yang secara eksplisit menampilkan `.sibk-operational-report-table` dan menyembunyikan `.sibk-operational-report-cards`, sehingga cetak dari viewport ponsel tetap menghasilkan tabel aktif.

- [x] **Step 7: Perbarui frontend checker**

Tambahkan pemeriksaan bahwa `reportIndex` mempunyai tiga label tab, `aria-current`, filter bernama `q` dan `classroom_id`, hidden `tab`, Reset tab aktif, desktop table, mobile card list, export route dari filter tervalidasi, print trigger/container, dan tidak lagi melakukan loop katalog kartu.

- [x] **Step 8: Jalankan verification task**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php
php artisan test tests/Feature/ReportManagementTest.php
php artisan test tests/Feature/FrontendPreviewTest.php
php artisan test tests/Feature/AuthorizationMatrixTest.php
npm.cmd run check:frontend
npm.cmd run build
php vendor/bin/pint --test
git diff --check
```

- [x] **Step 9: Commit**

```powershell
git add app/Http/Controllers/ReportController.php app/Services/OperationalReportRecapService.php routes/web.php resources/views/pages/reports/index.blade.php resources/views/pages/reports/_filters.blade.php resources/views/pages/reports/_desktop-table.blade.php resources/views/pages/reports/_mobile-cards.blade.php resources/scss/app-dashboard.scss scripts/check-frontend.mjs tests/Feature/OperationalReportRecapTest.php tests/Feature/ReportManagementTest.php tests/Feature/FrontendPreviewTest.php tests/Feature/AuthorizationMatrixTest.php
git commit -m "feat: satukan laporan Guru BK dalam tiga tab"
```

---

### Task 7: Verification CLI dan Handoff UAT Manual Laporan

> Status 15 September 2026: Task 1–7 selesai. Gate CLI lulus pada SQLite
> (418 test/3.293 assertion) dan focused MySQL disposable (16 test/148
> assertion); scan privasi/dependency bersih. UAT manual oleh `ui` melalui
> Chrome pada tiga viewport dinyatakan PASS tanpa temuan gagal; versi browser
> tidak dilaporkan. Task 8 dimulai setelah branch terintegrasi ke `cobasidebar`.

**Files:**
- Create: `docs/testing/2026-09-14-uat-laporan-guru-koordinator.md`
- Modify: `docs/development-log.md`
- Modify: `docs/superpowers/plans/2026-09-14-penyederhanaan-laporan-guru-koordinator.md`

**Interfaces:**
- Consumes: seluruh task sebelumnya.
- Produces: evidence gate CLI dan checklist UAT manual; fase laporan baru
  selesai setelah hasil manual dilaporkan PASS.

- [x] **Step 1: Jalankan focused gate melalui CLI**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php
php artisan test tests/Feature/ReportManagementTest.php
php artisan test tests/Feature/AchievementManagementTest.php
php artisan test tests/Feature/AuthorizationMatrixTest.php
php artisan test tests/Feature/FrontendPreviewTest.php
```

Expected: 0 failure dan 0 error.

- [x] **Step 2: Jalankan full automated gate melalui CLI**

```powershell
php artisan test
php vendor/bin/pint --test
composer validate --strict
php artisan config:cache
php artisan view:cache
npm.cmd run check:frontend
npm.cmd run build
git diff --check
php artisan config:clear
php artisan view:clear
```

Expected: seluruh command exit code 0 dan cache dibersihkan setelah verifikasi.

- [x] **Step 3: Jalankan privacy dan dependency scan**

```powershell
rg -n "initial_info|internal_note|final_result|next_plan|evidence_reference|evidence_description|verification_notes" resources/views/pages/reports app/Services/OperationalReportRecapService.php
rg -n "spatie/laravel-query-builder|yajra|livewire|powergrid|filament|jquery" composer.json composer.lock package.json package-lock.json
```

Expected: field sensitif tidak diakses oleh view/service output; dependency tabel baru tidak ada.

- [x] **Step 4: Verifikasi query pada SQLite dan MySQL disposable**

Jalankan focused test rekap pada SQLite test dan database MySQL disposable yang telah divalidasi bukan shared/production. Jangan memakai `migrate:fresh`, rollback, atau reset pada database shared.

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php
```

Pada MySQL disposable gunakan konfigurasi test yang sudah tersedia dan catat nama database serta hasil tanpa mencetak credential.

- [x] **Step 5: Siapkan dan serahkan checklist UAT manual**

Pelaksana UAT adalah pengguna atau tester manusia yang membuka aplikasi melalui
browser biasa. Agent hanya menyiapkan data/akun uji, URL awal, dan checklist;
agent tidak menjalankan browser automation, headless browser, CUA, screenshot
comparison, atau menyimpulkan kualitas visual. Sebelum hasil dikembalikan,
catat status UAT sebagai `PENDING MANUAL` dan hentikan eksekusi sebelum Task 8.

Viewport:

```text
1440 x 900
768 x 1024
390 x 844
```

Skenario:

```text
Guru BK hanya melihat murid dalam scope.
Koordinator melihat rekap gabungan.
Tiga tab dapat dibuka langsung melalui URL.
Pencarian nama dan filter kelas bekerja pada setiap tab.
Filter Guru BK hanya tampil pada tab Layanan untuk Koordinator.
Tabel desktop dan kartu mobile menampilkan nilai yang sama.
Identitas sementara Layanan mempunyai penanda dan tidak tergabung karena nama.
Cetak hanya memuat tab aktif.
CSV memakai filter aktif dan tidak memuat data sensitif.
Reset filter kembali ke tab aktif.
Tidak ada horizontal overflow halaman.
Urutan fokus mengikuti urutan visual dan focus ring terlihat.
Endpoint legacy masih dapat dibuka oleh role sah.
```

- [x] **Step 6: Terima dan catat hasil UAT manual**

Setelah pelaksana manual mengirim hasil, isi laporan UAT dengan nama/inisial
tester, tanggal, browser dan versi, branch/SHA, viewport, skenario PASS/FAIL,
catatan temuan, jumlah test/assertion CLI, hasil query compatibility, build,
privacy scan, dan dependency scan. Jika ada skenario FAIL, kembalikan ke task
implementasi terkait, ulangi gate CLI yang terdampak, lalu serahkan skenario
manual tersebut untuk diuji ulang. Pindahkan pekerjaan laporan ke Completed
pada `docs/development-log.md` dan tandai hanya checkbox Task 1-7 setelah semua
gate CLI dan UAT manual PASS; jangan menandai Task 8-15 atau plan gabungan
sebagai selesai.

- [x] **Step 7: Commit evidence**

```powershell
git add docs/testing/2026-09-14-uat-laporan-guru-koordinator.md docs/development-log.md docs/superpowers/plans/2026-09-14-penyederhanaan-laporan-guru-koordinator.md
git commit -m "docs: catat verifikasi laporan Guru BK"
```

---

## Fase B - Penyederhanaan Operasional, Akun, dan Skema Data

### Task 8: Selaraskan Requirement, Kontrak API, dan Batas Skema

**Files:**
- Modify: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements-index.md`
- Modify: `docs/api-contract.md`
- Modify: `docs/superpowers/specs/2026-09-14-penyederhanaan-operasional-akun-dan-skema-data-design.md`

**Interfaces:**
- Consumes: kedua spec pada header plan dan batas API sekolah yang telah dikonfirmasi pengguna.
- Produces: requirement final untuk seluruh Task 9-15, termasuk lifecycle layanan, proses keluar murid, password sementara, fitur yang dihentikan, dan batas skema anti-spaghetti tanpa target jumlah tabel yang kaku.

- [ ] **Step 1: Tulis failing documentation contract test**

Tambahkan pemeriksaan pada `tests/Feature/SharedDevelopmentBaselineTest.php`:

```php
public function test_active_requirements_publish_the_simplified_operational_contract(): void
{
    $srs = file_get_contents(base_path('docs/requirements/SRS_Aplikasi_BK_v1.1.md'));
    $api = file_get_contents(base_path('docs/api-contract.md'));

    $this->assertIsString($srs);
    $this->assertIsString($api);
    $this->assertStringContainsString('dalam_proses', $srs);
    $this->assertStringContainsString('resmi_keluar', $srs);
    $this->assertStringContainsString('must_change_password', $srs);
    $this->assertStringContainsString('API sekolah tidak menentukan status keluar murid', $api);
    $this->assertStringNotContainsString('Koreksi data operasional harus diverifikasi', $srs);
    $this->assertStringNotContainsString('pemberitahuan operasional yang terkait pengguna', $srs);
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal**

```powershell
php artisan test tests/Feature/SharedDevelopmentBaselineTest.php --filter=simplified_operational_contract
```

Expected: FAIL karena baseline aktif masih memuat workflow koreksi/notifikasi dan belum memuat kontrak baru.

- [ ] **Step 3: Amendemen requirement aktif**

Ubah PRD/SRS v1.1 tanpa mengganti nama versi:

```text
- Hapus Koreksi Data, Notifikasi, dan Riwayat Perubahan dari arsitektur informasi.
- Ubah CASE-04/CASE-14 dan CONS-03: status layanan aktif hanya baru,
  sedang_diproses, membutuhkan_tindak_lanjut, dan selesai.
- Hanya owner Guru BK yang dapat mengedit atau mengarsip; edit selesai
  memerlukan konfirmasi dan alasan, tetapi status/closed_at/identitas/owner
  tetap immutable.
- AUD-01 tetap append-only dan tidak mempunyai UI pembaca pada MVP.
- Dashboard mengganti Aktivitas Terbaru dengan informasi role-aware.
- ACC-01 memuat password sementara unik, expiry 24 jam, session invalidation,
  dan wajib ganti password.
- Student departure memakai status dalam_proses, batal, resmi_keluar;
  hanya resmi_keluar dengan effective_date yang memulai retensi.
```

Perbarui traceability matrix dan entitas konseptual. Hapus `Koreksi` serta
`Notifikasi` dari field minimum/entitas aktif. Tambahkan `Student Departure`
dengan field persis pada spec. Pertahankan dokumen v1.0 byte-for-byte di repository arsip privat terpisah.

- [ ] **Step 4: Dokumentasikan batas API sekolah**

Tambahkan kontrak eksplisit berikut pada `docs/api-contract.md`:

```text
Provider roster menyediakan nama, NISN, rombel, dan tahun_pelajaran.
Provider e-Tatib menyediakan NISN, nama, kelas, pelanggaran, poin,
pencatat, kategori, tanggal_pelanggaran, dan total_poin.
Kedua provider tidak menyediakan status/tanggal keluar murid.
Sinkronisasi provider dilarang membuat atau mengubah student_departures.
Ketiadaan field keluar tidak menggagalkan admission untuk fungsi roster,
tetapi dicatat sebagai batas kontrak dan tidak boleh diisi dengan asumsi.
```

Jangan menyalin URL, credential, nama file API lokal, atau nilai rahasia ke
repository.

- [ ] **Step 5: Verifikasi dokumen**

```powershell
php artisan test tests/Feature/SharedDevelopmentBaselineTest.php --filter=simplified_operational_contract
rg -n "Koreksi Data|Notifikasi|Riwayat Perubahan|dibatalkan|student_departures|resmi_keluar|must_change_password" docs/requirements/PRD_Aplikasi_BK_v1.1.md docs/requirements/SRS_Aplikasi_BK_v1.1.md docs/requirements-index.md docs/api-contract.md
git diff --check
```

Expected: test PASS; istilah lama hanya muncul pada riwayat amandemen atau
penjelasan penghentian, bukan sebagai fitur aktif.

- [ ] **Step 6: Commit**

```powershell
git add docs/requirements/PRD_Aplikasi_BK_v1.1.md docs/requirements/SRS_Aplikasi_BK_v1.1.md docs/requirements-index.md docs/api-contract.md docs/superpowers/specs/2026-09-14-penyederhanaan-operasional-akun-dan-skema-data-design.md tests/Feature/SharedDevelopmentBaselineTest.php
git commit -m "docs: selaraskan kontrak operasional Ruang BK"
```

---

### Task 9: Ganti Koreksi Terminal dengan Edit Beralasan dan Arsip

**Files:**
- Create: `database/migrations/2026_09_14_000050_retire_cancelled_service_records.php`
- Modify: `database/seeders/ReferenceSeeder.php`
- Modify: `app/Support/ServiceRecordStatus.php`
- Modify: `app/Policies/CasePolicy.php`
- Modify: `app/Policies/ConsultationPolicy.php`
- Modify: `app/Http/Requests/UpdateCaseRequest.php`
- Modify: `app/Http/Requests/UpdateConsultationRequest.php`
- Create: `app/Http/Requests/ArchiveCaseRequest.php`
- Create: `app/Http/Requests/ArchiveConsultationRequest.php`
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `app/Http/Controllers/ConsultationController.php`
- Modify: `app/Services/CaseService.php`
- Modify: `app/Services/ConsultationService.php`
- Modify: `routes/web.php`
- Modify: `routes/bk-services.php`
- Modify: `resources/views/pages/cases/index.blade.php`
- Modify: `resources/views/pages/cases/show.blade.php`
- Modify: `resources/views/pages/cases/edit.blade.php`
- Modify: `resources/views/pages/consultations/create.blade.php`
- Modify: `resources/views/pages/consultations/show.blade.php`
- Create: `resources/views/components/terminal-edit-confirmation.blade.php`
- Modify: `tests/Feature/CaseManagementTest.php`
- Modify: `tests/Feature/ConsultationManagementTest.php`
- Modify: `tests/Feature/ServiceRecordStatusMigrationTest.php`
- Modify: `tests/Unit/ServiceRecordStatusTest.php`

**Interfaces:**
- Consumes: `CaseAssignment::TYPE_OWNER`, `AuditService::record()`, Eloquent `SoftDeletes`, dan status reference existing.
- Produces: reference kasus/konsultasi tanpa pilihan `dibatalkan`, `CasePolicy::archive()`, `ConsultationPolicy::archive()`, `CaseService::archive()`, `ConsultationService::archive()`, serta edit `selesai` dengan `change_reason`.

- [ ] **Step 1: Tulis failing test lifecycle kasus**

Ganti test yang mengharuskan koreksi terminal dengan kontrak berikut:

```php
public function test_completed_case_owner_can_edit_with_reason_but_cannot_change_terminal_state(): void
{
    [$owner, $student] = $this->teacherAndScopedStudent();
    $case = $this->createCase($owner, $student);
    $this->actingAs($owner)->post(route('cases.resolve', $case), [
        'closed_at' => '2026-08-20',
        'final_result' => 'Tujuan layanan tercapai.',
        'resolution_summary' => 'Kasus ditutup setelah tindak lanjut.',
        'continued_plan' => 'Pemantauan berkala.',
        'waka_summary' => 'Penanganan selesai dengan pemantauan berkala.',
    ])->assertRedirect(route('cases.show', $case));
    $case->refresh();
    $payload = [
        ...$this->caseUpdatePayload($case),
        'initial_action' => 'Penanganan diperjelas setelah penutupan.',
        'status_id' => $case->status_id,
        'change_reason' => 'Memperbaiki ringkasan berdasarkan catatan sesi resmi.',
    ];

    $this->actingAs($owner)->patch(route('cases.update', $case), $payload)
        ->assertRedirect(route('cases.show', $case));

    $case->refresh();
    $this->assertSame('selesai', $case->status->code);
    $this->assertNotNull($case->closed_at);
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'case.completed_record_updated',
        'auditable_type' => BkCase::class,
        'auditable_id' => $case->id,
    ]);
}

public function test_completed_case_rejects_missing_reason_and_non_owner_direct_requests(): void
{
    [$owner, $student] = $this->teacherAndScopedStudent();
    $case = $this->createCase($owner, $student);
    app(CaseService::class)->resolve($case, [
        'closed_at' => '2026-08-20',
        'final_result' => 'Selesai.',
        'resolution_summary' => 'Penyelesaian tercatat.',
        'waka_summary' => 'Kasus selesai setelah penanganan sesuai kebutuhan.',
    ], $owner);
    $case->refresh();
    $other = $this->userWithRole('guru_bk');

    $this->actingAs($owner)->patch(route('cases.update', $case), [
        ...$this->caseUpdatePayload($case), 'status_id' => $case->status_id,
    ])->assertSessionHasErrors('change_reason');

    $this->actingAs($other)->get(route('cases.edit', $case))->assertForbidden();
    $this->actingAs($other)->delete(route('cases.destroy', $case))->assertForbidden();
}

public function test_case_delete_archives_and_excludes_record_from_operational_queries(): void
{
    [$owner, $student] = $this->teacherAndScopedStudent();
    $case = $this->createCase($owner, $student);

    $this->actingAs($owner)->delete(route('cases.destroy', $case))
        ->assertRedirect(route('cases.index'));

    $this->assertSoftDeleted('cases', ['id' => $case->id]);
    $this->actingAs($owner)->get(route('cases.show', $case))->assertNotFound();
}
```

Tambahkan test bahwa Koordinator, Waka, Admin IT, additional assignee, dan Guru
BK penerus tidak dapat edit/arsip record milik owner lain.

- [ ] **Step 2: Tulis failing test lifecycle konsultasi**

```php
public function test_completed_consultation_owner_can_edit_with_reason_and_archive(): void
{
    [$owner, $student] = $this->teacherAndScopedStudent();
    $consultation = app(ConsultationService::class)->create([
        ...$this->payload(ServiceRecordStatus::COMPLETED),
        'student_id' => $student->id,
    ], $owner);

    $this->actingAs($owner)->patch(route('consultations.update', $consultation), [
        ...$this->payload('selesai'),
        'status_id' => $consultation->status_id,
        'topic' => 'Topik yang telah diperbaiki',
        'change_reason' => 'Memperjelas topik sesuai catatan layanan resmi.',
    ])->assertRedirect(route('consultations.show', $consultation));

    $this->assertSame('selesai', $consultation->refresh()->status->code);

    $this->actingAs($owner)->delete(route('consultations.destroy', $consultation))
        ->assertRedirect(route('cases.index', ['tab' => 'konsultasi']));
    $this->assertSoftDeleted('consultations', ['id' => $consultation->id]);
}
```

Tambahkan assertion bahwa `change_reason` 9 karakter ditolak, 10-500 diterima,
501 ditolak, dan status selesai tidak dapat diganti melalui forged request.

- [ ] **Step 3: Jalankan tests dan pastikan gagal**

```powershell
php artisan test tests/Feature/CaseManagementTest.php --filter="completed_case|case_delete"
php artisan test tests/Feature/ConsultationManagementTest.php --filter=completed_consultation
```

Expected: FAIL karena policy, route arsip, dan direct terminal edit belum tersedia.

- [ ] **Step 4: Sederhanakan kontrak status layanan**

Ubah `ServiceRecordStatus` menjadi:

```php
final class ServiceRecordStatus
{
    public const string NEW = 'baru';
    public const string IN_PROGRESS = 'sedang_diproses';
    public const string NEEDS_FOLLOW_UP = 'membutuhkan_tindak_lanjut';
    public const string COMPLETED = 'selesai';

    private const array CODES = [
        self::NEW,
        self::IN_PROGRESS,
        self::NEEDS_FOLLOW_UP,
        self::COMPLETED,
    ];

    private const array TERMINAL_CODES = [self::COMPLETED];
}
```

Hapus constant/label `CANCELLED`. Jangan mengubah status pembatalan pada
`follow_up_status` atau `coordination_status`.

- [ ] **Step 5: Retire status dibatalkan pada data fresh dan incremental**

Migration:

```php
$cancelledIds = DB::table('references')
    ->whereIn('category', ['case_status', 'consultation_status'])
    ->where('code', 'dibatalkan')
    ->pluck('id');

DB::table('cases')->whereIn('status_id', $cancelledIds)->whereNull('deleted_at')
    ->update(['deleted_at' => now(), 'updated_at' => now()]);
DB::table('consultations')->whereIn('status_id', $cancelledIds)->whereNull('deleted_at')
    ->update(['deleted_at' => now(), 'updated_at' => now()]);
DB::table('references')->whereIn('id', $cancelledIds)
    ->update(['is_active' => false, 'updated_at' => now()]);
```

`down()` kosong dengan komentar forward-only; jangan mengaktifkan kembali
reference atau mengosongkan `deleted_at`, karena migration tidak dapat
membedakan arsip lama dari arsip hasil konversi secara aman. `ReferenceSeeder`
tidak lagi membuat `dibatalkan` untuk category kasus/konsultasi dan
menonaktifkan row legacy bila seeder dijalankan incremental.
Perbarui `ServiceRecordStatusMigrationTest` agar membuktikan kasus/konsultasi
legacy diarsipkan, reference inactive, dan follow-up/coordination cancellation
tetap tersedia.

- [ ] **Step 6: Ubah policy kepemilikan**

Gunakan aturan executable berikut:

```php
public function update(User $user, BkCase $case): bool
{
    return $user->hasRole('guru_bk') && $case->hasActiveOwnerFor($user);
}

public function archive(User $user, BkCase $case): bool
{
    return $this->update($user, $case);
}

public function resolve(User $user, BkCase $case): bool
{
    return ! ServiceRecordStatus::isTerminal($case->status?->code)
        && $this->update($user, $case);
}
```

Untuk konsultasi, `update()` dan `archive()` mensyaratkan role `guru_bk`,
`counselor_id === user.id`, dan `isProfessionallyAccessibleTo($user)`. Method
assign/coordinate kasus tetap menolak status terminal.

- [ ] **Step 7: Validasi edit selesai di Form Request**

Pada `UpdateCaseRequest`, gunakan `$record = $this->route('case')` dan
`$category = 'case_status'`. Pada `UpdateConsultationRequest`, gunakan
`$record = $this->route('consultation')` dan `$category =
'consultation_status'`. Setelah memastikan object sesuai model request, panggil
`$record->loadMissing('status')` dan set `$isCompleted =
$record->status?->code === ServiceRecordStatus::COMPLETED`. Terapkan:

```php
'change_reason' => [
    Rule::requiredIf($isCompleted),
    'nullable',
    'string',
    'min:10',
    'max:500',
],
'status_id' => $isCompleted
    ? ['required', 'integer', Rule::in([(int) $record->status_id])]
    : ['required', 'integer', Rule::exists('references', 'id')->where(
        fn ($statuses) => $statuses
            ->where('category', $category)
            ->where('is_active', true)
            ->where('code', '!=', ServiceRecordStatus::COMPLETED),
    )],
```

Tambahkan pesan `Alasan perubahan wajib diisi untuk data yang telah selesai.`.
Pastikan `closed_at`, identitas, `counselor_id`, dan owner assignment tidak ada
pada allowlist request edit.

- [ ] **Step 8: Implementasikan service update dan archive**

Di dalam transaksi, lock row, load status, dan periksa owner kembali sebelum
mutasi. Kontrak service:

```php
public function archive(BkCase $case, User $actor): void;
public function archive(Consultation $consultation, User $actor): void;
```

Untuk edit selesai, simpan `change_reason` hanya dalam `after_values` audit:

```php
$action = $isCompleted ? 'case.completed_record_updated' : 'case.updated';
$after = $this->snapshot($case->refresh());
if ($isCompleted) {
    $after['change_reason'] = trim((string) $data['change_reason']);
}
$this->auditService->record($action, $case, 'Data kasus diperbarui.', $actor, $before, $after);
```

Gunakan pola yang sama untuk `consultation.completed_record_updated`. Sebelum
`delete()`, audit action `case.archived` atau `consultation.archived`; jangan
menghapus relasi histori dan jangan mengubah status menjadi nilai lain.

- [ ] **Step 9: Ganti UX Batalkan dengan Edit/Hapus**

Ganti route lama:

```php
Route::delete('/cases/{case}', [CaseController::class, 'destroy'])->name('cases.destroy');
Route::delete('/consultations/{consultation}', [ConsultationController::class, 'destroy'])->name('consultations.destroy');
```

Untuk status selesai, `edit()` tetap diizinkan owner. Bila query
`confirm_terminal=1` belum ada, view hanya merender komponen:

```blade
<x-terminal-edit-confirmation
    :continue-url="route($continueRoute, [$record, 'confirm_terminal' => 1])"
    :cancel-url="route($cancelRoute, $record)"
/>
```

Komponen menampilkan teks persis `Data ini telah dinyatakan selesai. Apakah
Anda setuju melanjutkan pengeditan?`, tombol `Lanjutkan Edit`, dan `Kembali`.
Form terminal menampilkan `Alasan perubahan`; status ditampilkan read-only dan
tetap dikirim sebagai hidden input. Semua tombol Hapus memakai form `DELETE`
dengan `data-confirm-submit` dan pesan `Data akan diarsipkan dan tidak tampil
pada daftar utama. Lanjutkan?`.

- [ ] **Step 10: Verifikasi lifecycle**

```powershell
php artisan test tests/Feature/CaseManagementTest.php
php artisan test tests/Feature/ConsultationManagementTest.php
php artisan test tests/Feature/ServiceRecordStatusMigrationTest.php
php artisan test tests/Unit/ServiceRecordStatusTest.php
php vendor/bin/pint --test database/migrations/2026_09_14_000050_retire_cancelled_service_records.php database/seeders/ReferenceSeeder.php app/Support/ServiceRecordStatus.php app/Policies/CasePolicy.php app/Policies/ConsultationPolicy.php app/Http/Requests/UpdateCaseRequest.php app/Http/Requests/UpdateConsultationRequest.php app/Http/Requests/ArchiveCaseRequest.php app/Http/Requests/ArchiveConsultationRequest.php app/Http/Controllers/CaseController.php app/Http/Controllers/ConsultationController.php app/Services/CaseService.php app/Services/ConsultationService.php tests/Feature/CaseManagementTest.php tests/Feature/ConsultationManagementTest.php tests/Feature/ServiceRecordStatusMigrationTest.php tests/Unit/ServiceRecordStatusTest.php
git diff --check
```

- [ ] **Step 11: Commit**

```powershell
git add database/migrations/2026_09_14_000050_retire_cancelled_service_records.php database/seeders/ReferenceSeeder.php app/Support/ServiceRecordStatus.php app/Policies/CasePolicy.php app/Policies/ConsultationPolicy.php app/Http/Requests/UpdateCaseRequest.php app/Http/Requests/UpdateConsultationRequest.php app/Http/Requests/ArchiveCaseRequest.php app/Http/Requests/ArchiveConsultationRequest.php app/Http/Controllers/CaseController.php app/Http/Controllers/ConsultationController.php app/Services/CaseService.php app/Services/ConsultationService.php routes/web.php routes/bk-services.php resources/views/components/terminal-edit-confirmation.blade.php resources/views/pages/cases/index.blade.php resources/views/pages/cases/show.blade.php resources/views/pages/cases/edit.blade.php resources/views/pages/consultations/create.blade.php resources/views/pages/consultations/show.blade.php tests/Feature/CaseManagementTest.php tests/Feature/ConsultationManagementTest.php tests/Feature/ServiceRecordStatusMigrationTest.php tests/Unit/ServiceRecordStatusTest.php
git commit -m "feat: sederhanakan edit dan arsip layanan BK"
```

---

### Task 10: Hentikan Koreksi, Notifikasi, Riwayat, dan Aktivitas Audit UI

**Files:**
- Delete: `app/Http/Controllers/CorrectionController.php`
- Delete: `app/Http/Controllers/NotificationController.php`
- Delete: `app/Http/Controllers/HistoryController.php`
- Delete: `app/Http/Requests/StoreCorrectionRequest.php`
- Delete: `app/Http/Requests/VerifyCorrectionRequest.php`
- Delete: `app/Http/Requests/ProcessMasterCorrectionRequest.php`
- Delete: `app/Models/Correction.php`
- Delete: `app/Models/UserNotification.php`
- Delete: `app/Policies/CorrectionPolicy.php`
- Delete: `app/Policies/UserNotificationPolicy.php`
- Delete: `app/Services/CorrectionService.php`
- Delete: `app/Services/NotificationService.php`
- Delete: `resources/views/pages/corrections/`
- Delete: `resources/views/pages/notifications/`
- Delete: `resources/views/pages/history/`
- Delete: `tests/Feature/CorrectionManagementTest.php`
- Rename: `tests/Feature/DashboardNotificationTest.php` to `tests/Feature/DashboardTest.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Student.php`
- Modify: `app/Models/BkCase.php`
- Modify: `app/Models/Consultation.php`
- Modify: `app/Models/FollowUp.php`
- Modify: `app/Models/Achievement.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Services/AssignmentService.php`
- Modify: `app/Services/CaseService.php`
- Modify: `app/Services/ConsultationService.php`
- Modify: `app/Services/AchievementService.php`
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Http/Controllers/LegacyPreviewController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/components/sidebar.blade.php`
- Modify: `resources/views/pages/students/show.blade.php`
- Modify: `resources/views/pages/cases/show.blade.php`
- Modify: `resources/views/pages/consultations/show.blade.php`
- Modify: `resources/views/pages/achievements/show.blade.php`
- Modify: `scripts/check-frontend.mjs`
- Modify: `tests/Feature/AuthorizationMatrixTest.php`
- Modify: `tests/Feature/FrontendPreviewTest.php`
- Modify: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Consumes: direct terminal edit dari Task 9 dan `AuditService` append-only.
- Produces: tidak ada consumer runtime untuk tabel `corrections` atau `user_notifications`; dashboard tidak membaca daftar audit.

- [ ] **Step 1: Tulis failing retired-feature tests**

Tambahkan ke `AuthorizationMatrixTest`:

```php
public function test_retired_feature_routes_are_not_registered(): void
{
    $user = $this->userWithRole('admin_it');

    foreach ([
        '/corrections', '/notifications', '/history',
        '/_preview/notifications', '/_preview/corrections',
        '/_preview/corrections/create', '/_preview/corrections/show',
        '/_preview/history',
    ] as $uri) {
        $this->actingAs($user)->get($uri)->assertNotFound();
    }

    $routes = collect(Route::getRoutes())->map->getName()->filter();
    $this->assertFalse($routes->contains(fn (string $name): bool =>
        str_starts_with($name, 'corrections.')
        || str_starts_with($name, 'notifications.')
        || str_starts_with($name, 'history.')
        || str_starts_with($name, 'fixtures.notifications')
        || str_starts_with($name, 'fixtures.corrections')
        || str_starts_with($name, 'fixtures.history')
    ));
}
```

Tambahkan test dashboard:

```php
public function test_dashboard_uses_role_context_instead_of_audit_activity_feed(): void
{
    $teacher = $this->userWithRole('guru_bk');
    AuditLog::query()->create([
        'actor_id' => $teacher->id,
        'action' => 'secret.event',
        'auditable_type' => User::class,
        'auditable_id' => $teacher->id,
        'summary' => 'NARASI-AUDIT-RAHASIA',
    ]);

    $this->actingAs($teacher)->get(route('dashboard.preview'))
        ->assertOk()
        ->assertSee('Cakupan layanan Anda')
        ->assertDontSee('Aktivitas Terbaru')
        ->assertDontSee('NARASI-AUDIT-RAHASIA');
}
```

- [ ] **Step 2: Jalankan tests dan pastikan gagal**

```powershell
php artisan test tests/Feature/AuthorizationMatrixTest.php --filter=retired_feature
php artisan test tests/Feature/DashboardNotificationTest.php --filter=role_context
```

- [ ] **Step 3: Hapus route, policy registration, dan view composer**

Hapus seluruh route koreksi/notifikasi/riwayat beserta route fixture
`/_preview/notifications`, `/_preview/corrections`,
`/_preview/corrections/create`, `/_preview/corrections/show`, dan
`/_preview/history`. `LegacyPreviewController` tetap dipakai untuk bookmark
`/_preview` lain yang destination-nya masih sah. Hapus policy model tersebut,
gate `viewAuditHistory`, dan composer
`unreadNotificationCount` dari `AppServiceProvider`. Sidebar tidak boleh
mengandung label, badge, atau route retired.

- [ ] **Step 4: Hapus dependency service dan relasi model**

Hapus constructor dependency `NotificationService` dan seluruh pemanggilan
`send()` dari Assignment, Case, Consultation, dan koordinasi. Hapus method
`applyApprovedCorrection()` dari service domain dan relasi morph `corrections()`
dari Student, BkCase, Consultation, FollowUp, dan Achievement. Hapus relasi
`submittedCorrections()`, `reviewedCorrections()`, dan `notifications()` dari
User serta trait `Notifiable` bila tidak ada consumer lain.

Gunakan audit existing untuk mutasi penting; jangan mengganti notifikasi dengan
event audit yang ditampilkan ke pengguna.

- [ ] **Step 5: Ganti payload dashboard**

`DashboardService` tidak lagi mengimpor `AuditLog` atau `Correction`. Payload
operasional menghasilkan key berikut:

```php
'context_panel' => [
    'title' => match ($mode) {
        'teacher' => 'Cakupan layanan Anda',
        'coordinator' => 'Kesiapan penugasan BK',
    },
    'items' => match ($mode) {
        'teacher' => $this->teacherCoverageItems($user, $year),
        'coordinator' => $this->coordinatorCoverageItems($year),
    },
],
```

Tambahkan helper dengan kontrak eksplisit:

```php
/** @return list<array{label: string, value: string, meta: string}> */
private function teacherCoverageItems(User $user, ?AcademicYear $year): array;

/** @return list<array{label: string, value: string, meta: string}> */
private function coordinatorCoverageItems(?AcademicYear $year): array;

/** @return list<array{label: string, value: string, meta: string}> */
private function technicalReadinessItems(?AcademicYear $year): array;
```

Item Guru BK berisi jumlah kelas ampuan, kasus khusus aktif, dan tindak lanjut
terdekat. Item Koordinator berisi Guru BK aktif, kelas tanpa penugasan efektif,
dan tindak lanjut terbuka. Method `technical()` Admin IT menambahkan
`context_panel` berjudul `Kesiapan data dan integrasi` dari
`technicalReadinessItems()`: akun aktif, konflik sinkronisasi belum selesai,
tahun ajaran aktif, serta status provider tanpa credential. Waka tetap memakai
`WakaDashboardService` dengan ringkasan aman. Hapus key `activities`, method
`activityItems()`, dan `scopedAuditQuery()`.

- [ ] **Step 6: Ganti view dan frontend checker**

Pada dashboard, ganti heading `Aktivitas Terbaru` dengan
`$dashboard['context_panel']['title']` dan render hanya label/value/meta aman.
Hapus tombol `Ajukan Koreksi` dari profil murid, konsultasi, kasus, dan prestasi.
Perbarui frontend checker agar memastikan string/route retired tidak ada.

- [ ] **Step 7: Hapus artefak dan sesuaikan tests**

Hapus file yang tercantum sebagai Delete. Pertahankan test dashboard dari
`DashboardNotificationTest` di file baru `DashboardTest`; hapus seluruh test
notifikasi. Hapus URI retired dari matrix dan tambahkan assertion 404 terpisah.

- [ ] **Step 8: Verifikasi tidak ada consumer**

```powershell
rg -n "Correction|corrections\.|UserNotification|NotificationService|notifications\.|history\.|unreadNotificationCount|Aktivitas Terbaru|Ajukan Koreksi" app routes resources tests scripts
php artisan test tests/Feature/DashboardTest.php
php artisan test tests/Feature/AuthorizationMatrixTest.php
php artisan test tests/Feature/FrontendPreviewTest.php
npm.cmd run check:frontend
php vendor/bin/pint --test
git diff --check
```

Expected: `rg` tidak menemukan consumer runtime; kemunculan yang tersisa hanya
pada migration lama atau dokumentasi histori yang sengaja dipertahankan.

- [ ] **Step 9: Commit**

```powershell
git add -A -- app/Http/Controllers/CorrectionController.php app/Http/Controllers/NotificationController.php app/Http/Controllers/HistoryController.php app/Http/Controllers/LegacyPreviewController.php app/Http/Requests/StoreCorrectionRequest.php app/Http/Requests/VerifyCorrectionRequest.php app/Http/Requests/ProcessMasterCorrectionRequest.php app/Models/Correction.php app/Models/UserNotification.php app/Models/User.php app/Models/Student.php app/Models/BkCase.php app/Models/Consultation.php app/Models/FollowUp.php app/Models/Achievement.php app/Policies/CorrectionPolicy.php app/Policies/UserNotificationPolicy.php app/Providers/AppServiceProvider.php app/Services/CorrectionService.php app/Services/NotificationService.php app/Services/AssignmentService.php app/Services/CaseService.php app/Services/ConsultationService.php app/Services/AchievementService.php app/Services/DashboardService.php routes/web.php resources/views/components/sidebar.blade.php resources/views/pages/corrections/create.blade.php resources/views/pages/corrections/index.blade.php resources/views/pages/corrections/show.blade.php resources/views/pages/notifications/index.blade.php resources/views/pages/history/index.blade.php resources/views/pages/students/show.blade.php resources/views/pages/cases/show.blade.php resources/views/pages/consultations/show.blade.php resources/views/pages/achievements/show.blade.php scripts/check-frontend.mjs tests/Feature/CorrectionManagementTest.php tests/Feature/DashboardNotificationTest.php tests/Feature/DashboardTest.php tests/Feature/AuthorizationMatrixTest.php tests/Feature/FrontendPreviewTest.php
git commit -m "refactor: hentikan fitur koreksi dan notifikasi"
```

---

### Task 11: Implementasikan Proses Keluar Murid Satu Baris per Murid

**Files:**
- Create: `database/migrations/2026_09_14_000100_create_student_departures_table.php`
- Create: `app/Models/StudentDeparture.php`
- Create: `app/Policies/StudentDeparturePolicy.php`
- Create: `app/Http/Requests/StoreStudentDepartureRequest.php`
- Create: `app/Http/Requests/UpdateStudentDepartureRequest.php`
- Create: `app/Http/Requests/FinalizeStudentDepartureRequest.php`
- Create: `app/Services/StudentDepartureService.php`
- Create: `app/Http/Controllers/StudentDepartureController.php`
- Create: `app/Services/WakaStudentDepartureService.php`
- Create: `app/Http/Controllers/WakaStudentDepartureController.php`
- Modify: `app/Models/Student.php`
- Modify: `app/Policies/StudentPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Http/Controllers/StudentController.php`
- Modify: `routes/web.php`
- Create: `resources/views/pages/students/_departure-process.blade.php`
- Create: `resources/views/pages/waka/student-departures/index.blade.php`
- Modify: `resources/views/pages/students/show.blade.php`
- Create: `tests/Feature/StudentDepartureTest.php`
- Create: `tests/Feature/WakaStudentDepartureTest.php`
- Modify: `tests/Feature/DapodikSyncTest.php`
- Modify: `tests/Feature/DelayedDapodikPreparationTest.php`

**Interfaces:**
- Consumes: `Student::professionallyAccessibleTo()`, role `guru_bk`/`koordinator_bk`, dan `AuditService`.
- Produces: `StudentDeparture`, `StudentDepartureService::record()`, `updateDraft()`, `finalize()`, scope `Student::availableForService()`, serta daftar/detail proses keluar read-only untuk Waka Kesiswaan.

- [ ] **Step 1: Tulis failing migration/model tests**

```php
public function test_one_student_has_only_one_departure_process(): void
{
    $student = Student::factory()->create();
    $teacher = $this->userWithRole('guru_bk');

    StudentDeparture::query()->create([
        'student_id' => $student->id,
        'departure_type' => StudentDeparture::TYPE_TRANSFER,
        'status' => StudentDeparture::STATUS_IN_PROGRESS,
        'reported_at' => '2026-09-14',
        'recorded_by' => $teacher->id,
    ]);

    $this->expectException(QueryException::class);
    StudentDeparture::query()->create([
        'student_id' => $student->id,
        'departure_type' => StudentDeparture::TYPE_WITHDRAWAL,
        'status' => StudentDeparture::STATUS_IN_PROGRESS,
        'reported_at' => '2026-09-15',
        'recorded_by' => $teacher->id,
    ]);
}
```

- [ ] **Step 2: Tulis failing authorization dan transition tests**

```php
public function test_teacher_records_process_without_deactivating_student(): void
{
    [$teacher, $student] = $this->scopedStudentFixture();

    $this->actingAs($teacher)->post(route('students.departure.store', $student), [
        'departure_type' => 'pindah',
        'reported_at' => '2026-09-14',
        'recommendation_summary' => 'Menunggu kelengkapan keputusan resmi sekolah.',
    ])->assertRedirect(route('students.show', $student));

    $this->assertDatabaseHas('student_departures', [
        'student_id' => $student->id,
        'status' => 'dalam_proses',
    ]);
    $this->assertTrue(Student::query()->availableForService()->whereKey($student->id)->exists());
}

public function test_only_coordinator_finalizes_and_official_exit_stops_new_service_scope(): void
{
    [$teacher, $student, $departure] = $this->departureFixture();
    $coordinator = $this->userWithRole('koordinator_bk');

    $this->actingAs($teacher)->post(route('students.departure.finalize', $student), [
        'decision' => 'resmi_keluar',
        'effective_date' => '2026-09-20',
    ])->assertForbidden();

    $this->travelTo('2026-09-20 08:00:00');
    $this->actingAs($coordinator)->post(route('students.departure.finalize', $student), [
        'decision' => 'resmi_keluar',
        'effective_date' => '2026-09-20',
        'decision_note' => 'Keputusan resmi sekolah telah diterima.',
    ])->assertRedirect(route('students.show', $student));

    $this->assertFalse(Student::query()->availableForService()->whereKey($student->id)->exists());
    $this->assertTrue(Student::query()->whereKey($student->id)->exists());
    $this->assertSame('resmi_keluar', $departure->refresh()->status);
}
```

Tambahkan test `batal` tidak menonaktifkan, proses batal dapat dibuka kembali
pada row yang sama, forged student di luar scope ditolak, Waka/Admin IT tidak
dapat mencatat/finalisasi, dan request paralel tidak menghasilkan row kedua.
Waka Kesiswaan tetap wajib dapat membaca daftar serta detail proses keluar.

Gunakan fixture konkret berikut pada test class baru:

```php
protected function setUp(): void
{
    parent::setUp();
    $this->seed([RoleSeeder::class, ReferenceSeeder::class]);
    $this->travelTo('2026-09-14 08:00:00');
}

/** @return array{User, Student} */
private function scopedStudentFixture(): array
{
    $teacher = $this->userWithRole('guru_bk');
    $year = AcademicYear::query()->create([
        'name' => '2026/2027', 'starts_on' => '2026-07-01',
        'ends_on' => '2027-06-30', 'is_active' => true,
    ]);
    $classroom = Classroom::query()->create([
        'academic_year_id' => $year->id, 'name' => 'XI RPL 1', 'is_active' => true,
    ]);
    $student = Student::query()->create([
        'nisn' => '0012345678', 'name' => 'Murid Proses Keluar', 'is_active' => true,
    ]);
    StudentClassMembership::query()->create([
        'student_id' => $student->id, 'classroom_id' => $classroom->id,
        'academic_year_id' => $year->id, 'effective_from' => '2026-07-01',
        'is_active' => true,
    ]);
    TeacherAssignment::query()->create([
        'user_id' => $teacher->id, 'classroom_id' => $classroom->id,
        'academic_year_id' => $year->id, 'effective_from' => '2026-07-01',
        'decision_number' => 'SK-DEPARTURE', 'assigned_by' => $teacher->id,
    ]);

    return [$teacher, $student];
}

/** @return array{User, Student, StudentDeparture} */
private function departureFixture(): array
{
    [$teacher, $student] = $this->scopedStudentFixture();
    $departure = app(StudentDepartureService::class)->record($student, [
        'departure_type' => 'pindah',
        'reported_at' => '2026-09-14',
        'recommendation_summary' => 'Menunggu keputusan resmi sekolah.',
    ], $teacher);

    return [$teacher, $student, $departure];
}

private function userWithRole(string $slug): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

    return $user;
}
```

- [ ] **Step 3: Jalankan tests dan pastikan gagal**

```powershell
php artisan test tests/Feature/StudentDepartureTest.php
```

Expected: FAIL karena migration, model, service, policy, dan route belum ada.

- [ ] **Step 4: Buat migration satu sumber kebenaran**

Gunakan schema berikut:

```php
Schema::create('student_departures', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
    $table->string('departure_type', 40);
    $table->string('status', 30)->default('dalam_proses')->index();
    $table->date('reported_at');
    $table->string('recommendation_summary', 500)->nullable();
    $table->date('effective_date')->nullable();
    $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('finalized_at')->nullable();
    $table->string('decision_note', 500)->nullable();
    $table->timestamps();
    $table->index(['status', 'effective_date']);
});
```

Jangan menambah departure flag/date pada `students` dan jangan memakai soft
delete pada process record. `down()` migration ini kosong dengan komentar
forward-only; jangan menghapus `student_departures` atau memulihkan state
sebelumnya melalui rollback.

- [ ] **Step 5: Buat model dan state contract**

```php
#[Fillable([
    'student_id', 'departure_type', 'status', 'reported_at',
    'recommendation_summary', 'effective_date', 'recorded_by',
    'finalized_by', 'finalized_at', 'decision_note',
])]
final class StudentDeparture extends Model
{
    public const string STATUS_IN_PROGRESS = 'dalam_proses';
    public const string STATUS_CANCELLED = 'batal';
    public const string STATUS_OFFICIAL = 'resmi_keluar';

    public const string TYPE_GRADUATED = 'lulus';
    public const string TYPE_TRANSFER = 'pindah';
    public const string TYPE_WITHDRAWAL = 'mengundurkan_diri';
    public const string TYPE_OTHER = 'keluar_lainnya';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    protected function casts(): array
    {
        return [
            'reported_at' => 'date',
            'effective_date' => 'date',
            'finalized_at' => 'datetime',
        ];
    }
}
```

Tambahkan relasi `Student::departure(): HasOne`. Tambahkan scope:

```php
public function scopeAvailableForService(Builder $query, CarbonInterface|string|null $date = null): Builder
{
    $on = CarbonImmutable::parse($date ?? now())->toDateString();

    return $query->active()->whereDoesntHave('departure', fn (Builder $departure): Builder =>
        $departure->where('status', StudentDeparture::STATUS_OFFICIAL)
            ->whereDate('effective_date', '<=', $on));
}
```

Gunakan scope ini pada daftar murid aktif dan seluruh form pencatatan layanan
baru. Query histori/detail tidak memakai scope ini agar data lama tidak hilang.

- [ ] **Step 6: Implementasikan policy dan request**

Policy:

```php
public function create(User $user, Student $student): bool
{
    return $user->hasRole('guru_bk')
        && Student::query()->professionallyAccessibleTo($user)->whereKey($student->getKey())->exists();
}

public function update(User $user, StudentDeparture $departure): bool
{
    return $departure->status === StudentDeparture::STATUS_IN_PROGRESS
        && $this->create($user, $departure->student);
}

public function finalize(User $user, StudentDeparture $departure): bool
{
    return $user->hasRole('koordinator_bk');
}
```

Store allowlist `departure_type`, `reported_at` dengan
`before_or_equal:today`, dan `recommendation_summary` maksimal 500. Update
hanya menerima `departure_type` dan `recommendation_summary`; `reported_at`
adalah tanggal pencatatan pertama dan tidak dapat diubah. Finalize Request
memakai:

```php
'decision' => ['required', Rule::in(['batal', 'resmi_keluar'])],
'effective_date' => [Rule::requiredIf($this->string('decision')->is('resmi_keluar')), 'nullable', 'date'],
'decision_note' => ['nullable', 'string', 'max:500'],
```

- [ ] **Step 7: Implementasikan service atomik**

```php
public function record(Student $student, array $data, User $actor): StudentDeparture;
public function updateDraft(StudentDeparture $departure, array $data, User $actor): StudentDeparture;
public function finalize(StudentDeparture $departure, array $data, User $actor): StudentDeparture;
```

`record()` memakai transaction, `Student::lockForUpdate()`, dan
`firstOrCreate(['student_id' => ...])`; bila row existing bukan `batal`, lempar
ValidationException. Bila existing `batal`, pertahankan `reported_at` pertama,
update jenis/ringkasan pada row yang sama ke `dalam_proses`, kosongkan field
finalisasi, lalu audit before/after. Tambahkan test bahwa pembukaan ulang tidak
mengubah `student_id`, `created_at`, atau `reported_at`.

`finalize()` lock row dan hanya menerima status `dalam_proses`. Untuk `batal`,
set `effective_date = null`; untuk `resmi_keluar`, wajibkan tanggal efektif.
Audit action: `student_departure.recorded`, `.updated`, `.cancelled`, atau
`.officialized`.

- [ ] **Step 8: Hubungkan controller, routes, dan UI minimal**

Routes:

```php
Route::post('/students/{student}/departure', [StudentDepartureController::class, 'store'])
    ->name('students.departure.store');
Route::patch('/students/{student}/departure', [StudentDepartureController::class, 'update'])
    ->name('students.departure.update');
Route::post('/students/{student}/departure/finalize', [StudentDepartureController::class, 'finalize'])
    ->name('students.departure.finalize');
```

Profil murid menampilkan satu panel `Proses keluar murid`. Guru BK dalam scope
melihat form catatan minimal; Koordinator melihat tombol `Tetapkan Batal` dan
`Tetapkan Resmi Keluar`. Untuk Guru BK/Koordinator, jangan membuat halaman
workflow terpisah, unggah dokumen, approval berlapis, atau timeline khusus.

Tambahkan halaman read-only Waka pada `/waka/student-departures`. Waka dapat
melihat identitas murid, kelas, jenis keluar, status, tanggal pelaporan, tanggal
efektif, ringkasan rekomendasi, catatan keputusan, pencatat, dan pemutus. Waka
tidak memperoleh aksi mutasi dan tidak otomatis memperoleh narasi privat kasus
atau konsultasi yang bukan bagian dari proses keluar.

- [ ] **Step 9: Pastikan API sekolah tidak mengubah departure**

Tambahkan test pada kedua jalur Dapodik:

```php
$admin = $this->userWithRole('admin_it');
$teacher = $this->userWithRole('guru_bk');
$snapshot = $this->snapshot();
$this->importSnapshot($snapshot, $admin);
$student = Student::query()->orderBy('id')->firstOrFail();
$departure = StudentDeparture::query()->create([
    'student_id' => $student->id,
    'departure_type' => 'pindah',
    'status' => 'dalam_proses',
    'reported_at' => '2026-09-14',
    'recorded_by' => $teacher->id,
]);
$before = $departure->only(['status', 'effective_date', 'finalized_by', 'finalized_at']);

$this->importSnapshot($snapshot, $admin);

$this->assertSame($before, $departure->refresh()->only(array_keys($before)));
```

Contract ini berlaku untuk data provisional, preview, apply, dan sync ulang.

- [ ] **Step 10: Verifikasi**

```powershell
php artisan test tests/Feature/StudentDepartureTest.php
php artisan test tests/Feature/WakaStudentDepartureTest.php
php artisan test tests/Feature/DapodikSyncTest.php
php artisan test tests/Feature/DelayedDapodikPreparationTest.php
php artisan test tests/Feature/StudentProfileTest.php
php vendor/bin/pint --test database/migrations/2026_09_14_000100_create_student_departures_table.php app/Models/StudentDeparture.php app/Policies/StudentDeparturePolicy.php app/Http/Requests/StoreStudentDepartureRequest.php app/Http/Requests/UpdateStudentDepartureRequest.php app/Http/Requests/FinalizeStudentDepartureRequest.php app/Services/StudentDepartureService.php app/Services/WakaStudentDepartureService.php app/Http/Controllers/StudentDepartureController.php app/Http/Controllers/WakaStudentDepartureController.php app/Models/Student.php app/Http/Controllers/StudentController.php tests/Feature/StudentDepartureTest.php tests/Feature/WakaStudentDepartureTest.php
git diff --check
```

- [ ] **Step 11: Commit**

```powershell
git add database/migrations/2026_09_14_000100_create_student_departures_table.php app/Models/StudentDeparture.php app/Policies/StudentDeparturePolicy.php app/Http/Requests/StoreStudentDepartureRequest.php app/Http/Requests/UpdateStudentDepartureRequest.php app/Http/Requests/FinalizeStudentDepartureRequest.php app/Services/StudentDepartureService.php app/Services/WakaStudentDepartureService.php app/Http/Controllers/StudentDepartureController.php app/Http/Controllers/WakaStudentDepartureController.php app/Models/Student.php app/Policies/StudentPolicy.php app/Providers/AppServiceProvider.php app/Http/Controllers/StudentController.php routes/web.php resources/views/pages/students/_departure-process.blade.php resources/views/pages/students/show.blade.php resources/views/pages/waka/student-departures/index.blade.php tests/Feature/StudentDepartureTest.php tests/Feature/WakaStudentDepartureTest.php tests/Feature/DapodikSyncTest.php tests/Feature/DelayedDapodikPreparationTest.php
git commit -m "feat: catat proses keluar murid secara terkendali"
```

---

### Task 12: Terapkan Password Sementara dan Pemulihan Admin IT

**Files:**
- Create: `database/migrations/2026_09_14_000200_add_temporary_password_fields_to_users.php`
- Create: `app/Data/TemporaryPasswordResult.php`
- Create: `app/Services/TemporaryPasswordService.php`
- Create: `app/Http/Requests/Admin/ResetUserPasswordRequest.php`
- Create: `app/Http/Requests/Auth/ChangePasswordRequest.php`
- Create: `app/Http/Controllers/Admin/UserPasswordResetController.php`
- Create: `app/Http/Controllers/AccountPasswordController.php`
- Create: `app/Http/Middleware/EnsurePasswordChanged.php`
- Create: `app/Console/Commands/ResetAdminPassword.php`
- Modify: `app/Http/Requests/Admin/StoreUserRequest.php`
- Modify: `app/Http/Requests/Admin/UpdateUserRequest.php`
- Modify: `app/Http/Controllers/Admin/UserManagementController.php`
- Modify: `app/Http/Controllers/AuthController.php`
- Modify: `app/Services/AccountService.php`
- Modify: `app/Models/User.php`
- Modify: `app/Policies/UserPolicy.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Modify: `config/sibk.php`
- Modify: `.env.example`
- Modify: `resources/views/pages/admin/users/index.blade.php`
- Create: `resources/views/pages/admin/users/temporary-password.blade.php`
- Create: `resources/views/pages/account/change-password.blade.php`
- Modify: `resources/views/pages/account/index.blade.php`
- Modify: `tests/Feature/AccountManagementTest.php`
- Modify: `tests/Feature/AuthenticationTest.php`
- Create: `tests/Feature/AdminPasswordRecoveryCommandTest.php`

**Interfaces:**
- Consumes: database session driver, `AuditService`, `UserPolicy`, dan active account middleware.
- Produces: `TemporaryPasswordService::issue(User, ?User, ?string): TemporaryPasswordResult`, `AccountService::create(array, User): TemporaryPasswordResult`, middleware alias `password.changed`, route ganti password, reset Admin IT, dan command `sibk:reset-admin-password`.

- [ ] **Step 1: Tulis failing account/password tests**

```php
public function test_admin_creates_account_with_one_time_temporary_password(): void
{
    $admin = $this->userWithRoles(['admin_it']);

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Guru Baru',
        'email' => 'guru.baru@example.test',
        'roles' => ['guru_bk'],
        'is_active' => '1',
    ]);

    $response->assertOk()
        ->assertHeader('cache-control', 'no-store, private')
        ->assertSee('Kata sandi sementara');
    $user = User::query()->where('email', 'guru.baru@example.test')->firstOrFail();
    $this->assertTrue($user->must_change_password);
    $this->assertNotNull($user->temporary_password_expires_at);
}

public function test_temporary_account_is_forced_to_change_password(): void
{
    $user = User::factory()->create([
        'password' => 'Sementara123',
        'must_change_password' => true,
        'temporary_password_expires_at' => now()->addHour(),
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'Sementara123',
    ])->assertRedirect(route('account.password.edit'));

    $this->get(route('dashboard.preview'))->assertRedirect(route('account.password.edit'));
}
```

Tambahkan test password expired ditolak setelah authentication, reset memutus
seluruh session target, Admin tidak dapat reset dirinya dari UI, password tidak
masuk audit/JSON model, dan password baru menghapus flag/expiry.

- [ ] **Step 2: Tulis failing command recovery test**

```php
public function test_single_admin_can_be_recovered_through_hidden_interactive_command(): void
{
    $this->seed(RoleSeeder::class);
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->where('slug', 'admin_it')->firstOrFail());

    $this->artisan('sibk:reset-admin-password')
        ->expectsQuestion('Email Admin IT', $admin->email)
        ->expectsQuestion('Kata sandi sementara baru', 'PulihAman123')
        ->expectsQuestion('Konfirmasi kata sandi sementara', 'PulihAman123')
        ->assertSuccessful();

    $admin->refresh();
    $this->assertTrue(Hash::check('PulihAman123', $admin->password));
    $this->assertTrue($admin->must_change_password);
}
```

Test tidak boleh mencetak password pada command output atau log.

- [ ] **Step 3: Jalankan tests dan pastikan gagal**

```powershell
php artisan test tests/Feature/AccountManagementTest.php --filter=temporary
php artisan test tests/Feature/AuthenticationTest.php --filter=change_password
php artisan test tests/Feature/AdminPasswordRecoveryCommandTest.php
```

- [ ] **Step 4: Tambahkan field account lifecycle**

Migration:

```php
Schema::table('users', function (Blueprint $table): void {
    $table->boolean('must_change_password')->default(false)->index();
    $table->timestamp('temporary_password_expires_at')->nullable();
    $table->timestamp('password_changed_at')->nullable();
});
```

Tambahkan fillable/cast pada User, tetapi jangan pernah menghapus `password`
dari hidden attributes. `down()` migration ini kosong dengan komentar
forward-only; jangan menghapus field lifecycle password atau mengubah kembali
data akun melalui rollback.

- [ ] **Step 5: Buat service hasil password sementara**

```php
final readonly class TemporaryPasswordResult
{
    public function __construct(
        public User $user,
        public string $plainTextPassword,
        public CarbonImmutable $expiresAt,
    ) {}
}
```

```php
public function issue(User $target, ?User $actor = null, ?string $plainText = null): TemporaryPasswordResult
{
    $password = $plainText ?? Str::password(16, true, true, false, false);
    $expiresAt = CarbonImmutable::now()->addHours((int) config('sibk.temporary_password_ttl_hours', 24));

    return DB::transaction(function () use ($target, $actor, $password, $expiresAt): TemporaryPasswordResult {
        $target->forceFill([
            'password' => $password,
            'must_change_password' => true,
            'temporary_password_expires_at' => $expiresAt,
        ])->save();
        DB::table('sessions')->where('user_id', $target->id)->delete();
        $this->auditService->record('account.temporary_password_issued', $target,
            'Kata sandi sementara akun diterbitkan.', $actor);

        return new TemporaryPasswordResult($target->refresh(), $password, $expiresAt);
    });
}
```

Snapshot audit dilarang memuat password. `AccountService::create()` memakai
service ini tepat satu kali dan mengembalikan `TemporaryPasswordResult`.
Kontraknya:

```php
public function create(array $data, User $actor): TemporaryPasswordResult
```

Dalam satu outer transaction, `AccountService` membuat user dengan password
bootstrap acak yang tidak pernah ditampilkan/disimpan di log, menyinkronkan
role, mencatat `account.created`, lalu memanggil
`TemporaryPasswordService::issue($user, $actor)`. Hanya `issue()` yang mengatur
password yang diberikan kepada pengguna, flag wajib ganti, expiry, pemutusan
sesi, dan audit penerbitan. Bila salah satu langkah gagal, pembuatan akun ikut
rollback. `UpdateUserRequest` tidak lagi menerima field password.

- [ ] **Step 6: Buat controller response satu kali**

Store/reset browser merender `temporary-password.blade.php` langsung, bukan
redirect/flash. `UserManagementController::store()` menerima hasil
`AccountService::create()`, sedangkan `UserPasswordResetController` memanggil
`TemporaryPasswordService::issue()` pada akun existing. Keduanya memakai
response satu kali berikut:

```php
return response()
    ->view('pages.admin.users.temporary-password', ['result' => $result])
    ->header('Cache-Control', 'no-store, private');
```

JSON response memakai struktur `temporary_password` dan `expires_at`, header
yang sama, serta tidak pernah menyertakan hash. Policy `resetPassword()` hanya
mengizinkan Admin IT aktif terhadap target lain.

- [ ] **Step 7: Paksa pergantian setelah login**

Setelah `Auth::attempt()`, bila flag true dan expiry sudah lewat: logout,
invalidate session, lalu kembalikan error `Kata sandi sementara telah
kedaluwarsa. Hubungi Admin IT.`. Bila belum lewat, redirect ke
`account.password.edit`.

Daftarkan alias:

```php
'password.changed' => EnsurePasswordChanged::class,
```

Susun route auth menjadi dua tingkat: route Logout dan Ganti Password berada
dalam `auth,account.active`; seluruh route operasional berada dalam nested
`password.changed`. Middleware mengarahkan akun ber-flag ke form ganti password.

ChangePasswordRequest memakai `current_password`, `confirmed`, minimum 8,
huruf, dan angka. Service menyimpan password baru, mengosongkan flag/expiry,
mengisi `password_changed_at`, menghapus session lain kecuali current session,
meregenerasi session, dan menulis audit tanpa password.

- [ ] **Step 8: Buat command pemulihan Admin IT**

Signature:

```php
protected $signature = 'sibk:reset-admin-password';
```

Gunakan `$this->ask('Email Admin IT')` dan `$this->secret(...)`; validasi target
aktif mempunyai role `admin_it`, kedua password sama, minimal 8 karakter,
mengandung huruf dan angka. Panggil `TemporaryPasswordService::issue($admin,
null, $password)`. Output hanya menyatakan akun berhasil dipulihkan dan wajib
ganti password; jangan menampilkan nilai password.

- [ ] **Step 9: Perbarui UI akun**

Hapus input password dari form buat/edit Admin IT. Tambahkan tombol `Reset Kata
Sandi` per akun selain akun actor. Halaman Akun Saya menyediakan link `Ganti
Kata Sandi`; halaman wajib ganti menjelaskan bahwa akses lain dibatasi sampai
password diperbarui.

- [ ] **Step 10: Verifikasi keamanan akun**

```powershell
php artisan test tests/Feature/AccountManagementTest.php
php artisan test tests/Feature/AuthenticationTest.php
php artisan test tests/Feature/AdminPasswordRecoveryCommandTest.php
php artisan route:list --name=account.password
php vendor/bin/pint --test database/migrations/2026_09_14_000200_add_temporary_password_fields_to_users.php app/Data/TemporaryPasswordResult.php app/Services/TemporaryPasswordService.php app/Http/Requests/Admin/ResetUserPasswordRequest.php app/Http/Requests/Auth/ChangePasswordRequest.php app/Http/Controllers/Admin/UserPasswordResetController.php app/Http/Controllers/AccountPasswordController.php app/Http/Middleware/EnsurePasswordChanged.php app/Console/Commands/ResetAdminPassword.php app/Services/AccountService.php app/Models/User.php tests/Feature/AccountManagementTest.php tests/Feature/AuthenticationTest.php tests/Feature/AdminPasswordRecoveryCommandTest.php
git grep -n -E "plainTextPassword|temporary_password|password" -- app/Services/AuditService.php
git diff --check
```

Expected: test membuktikan log/audit tidak memuat password, response satu kali
memakai `no-store`, dan source `AuditService` tidak menerima field password.
Bila log lokal perlu dibaca saat investigasi, gunakan hanya data test dan catat
jumlah/hasil teredaksi; jangan men-dump payload atau isi `storage/logs` ke
terminal maupun evidence.

- [ ] **Step 11: Commit**

```powershell
git add database/migrations/2026_09_14_000200_add_temporary_password_fields_to_users.php app/Data/TemporaryPasswordResult.php app/Services/TemporaryPasswordService.php app/Http/Requests/Admin/ResetUserPasswordRequest.php app/Http/Requests/Auth/ChangePasswordRequest.php app/Http/Controllers/Admin/UserPasswordResetController.php app/Http/Controllers/AccountPasswordController.php app/Http/Middleware/EnsurePasswordChanged.php app/Console/Commands/ResetAdminPassword.php app/Http/Requests/Admin/StoreUserRequest.php app/Http/Requests/Admin/UpdateUserRequest.php app/Http/Controllers/Admin/UserManagementController.php app/Http/Controllers/AuthController.php app/Services/AccountService.php app/Models/User.php app/Policies/UserPolicy.php bootstrap/app.php routes/web.php config/sibk.php .env.example resources/views/pages/admin/users/index.blade.php resources/views/pages/admin/users/temporary-password.blade.php resources/views/pages/account/change-password.blade.php resources/views/pages/account/index.blade.php tests/Feature/AccountManagementTest.php tests/Feature/AuthenticationTest.php tests/Feature/AdminPasswordRecoveryCommandTest.php
git commit -m "feat: wajibkan pergantian password sementara"
```

---

### Task 13: Audit dan Rapikan Skema Tanpa Target Jumlah Kaku

**Files:**
- Modify: `config/queue.php`
- Modify: `.env.example`
- Create: `tests/Feature/OperationalSchemaTest.php`

**Interfaces:**
- Consumes: tidak adanya consumer koreksi/notifikasi dari Task 10, migration status Task 9, serta migration departures/password Task 11-12.
- Produces: audit fresh dan incremental atas invariant skema, daftar consumer,
  serta keputusan pertahankan/hapus yang aman; jumlah tabel bukan gate.

> Amendemen keputusan produk: jangan membuat migration penghapusan tabel
> kandidat retired pada checkpoint ini. Task ini hanya melakukan audit
> kesehatan skema. Tabel hanya boleh dihapus melalui
> plan terpisah setelah tidak ada consumer, backup tersedia, pemulihan diuji,
> dan pengguna memberi persetujuan eksplisit.

- [ ] **Step 1: Tulis schema invariant test**

```php
public function test_operational_schema_keeps_required_sources_of_truth(): void
{
    foreach (['students', 'student_departures', 'cases', 'consultations',
        'sessions', 'cache', 'cache_locks', 'audit_logs'] as $table) {
        $this->assertTrue(Schema::hasTable($table), "Tabel wajib {$table} hilang.");
    }

    $this->assertTrue(Schema::hasColumns('student_departures', [
        'student_id', 'departure_type', 'status', 'effective_date',
    ]));
}
```

Tambahkan pemeriksaan unique `student_departures.student_id`, foreign key
domain, dan larangan tabel rekap/flag keluar duplikat. Tabel retired boleh
tetap ada secara fisik selama tidak mempunyai consumer runtime.

- [ ] **Step 2: Jalankan baseline schema test**

```powershell
php artisan test tests/Feature/OperationalSchemaTest.php
```

Expected: PASS setelah Task 9–12; kegagalan hanya menunjukkan invariant wajib
belum terpenuhi, bukan jumlah tabel yang berbeda dari 30.

- [ ] **Step 3: Audit enam tabel kandidat retired**

Audit `corrections`, `user_notifications`, `password_reset_tokens`, `jobs`,
`job_batches`, dan `failed_jobs` dengan `rg`, route list, model relation, config,
serta query runtime. Catat consumer yang sudah hilang dan pertahankan tabel
fisiknya. Jangan membuat migration drop pada checkpoint ini.

- [ ] **Step 4: Kunci queue synchronous**

Pastikan `.env.example` memuat:

```dotenv
QUEUE_CONNECTION=sync
SESSION_DRIVER=database
CACHE_STORE=database
```

`config/queue.php` tetap mendukung driver Laravel lain tetapi default memakai
`env('QUEUE_CONNECTION', 'sync')`. Jangan menghapus konfigurasi driver karena
queue dapat kembali setelah adapter production diterima.

- [ ] **Step 5: Verifikasi fresh dan incremental SQLite disposable**

Gate fresh boleh memakai `migrate:fresh --seed` pada database lokal/development
yang sudah dipastikan bukan shared/production. Gate otomatis tetap memakai file
SQLite disposable dan `php artisan migrate --force`. File harus tetap ada sebelum Laravel
membuka koneksi SQLite. Simpan lalu pulihkan nilai environment yang sebelumnya
ada, termasuk nilai kosong, dan teruskan exit code command pertama yang gagal.

```powershell
$freshDb = (New-TemporaryFile).FullName
$hadConnection = Test-Path Env:DB_CONNECTION
$oldConnection = $env:DB_CONNECTION
$hadDatabase = Test-Path Env:DB_DATABASE
$oldDatabase = $env:DB_DATABASE
$hadUrl = Test-Path Env:DB_URL
$oldUrl = $env:DB_URL
$gateExit = 0
php artisan config:clear
if ($LASTEXITCODE -ne 0) { $gateExit = $LASTEXITCODE }
try {
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = $freshDb
    $env:DB_URL = '(null)'
    if ($gateExit -eq 0) {
        php artisan tinker --execute="if (Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'sqlite' || Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== getenv('DB_DATABASE')) { throw new RuntimeException('Koneksi bukan SQLite disposable yang ditentukan.'); }"
        if ($LASTEXITCODE -ne 0) { $gateExit = $LASTEXITCODE }
    }
    if ($gateExit -eq 0) {
        php artisan migrate --force
        if ($LASTEXITCODE -ne 0) { $gateExit = $LASTEXITCODE }
    }
    if ($gateExit -eq 0) {
        php artisan tinker --execute="foreach (['students','student_departures','cases','consultations','sessions','cache','cache_locks','audit_logs'] as $table) { if (! Illuminate\Support\Facades\Schema::hasTable($table)) { throw new RuntimeException('Tabel wajib hilang: '.$table); } }"
        if ($LASTEXITCODE -ne 0) { $gateExit = $LASTEXITCODE }
    }
} finally {
    Remove-Item -LiteralPath $freshDb -ErrorAction SilentlyContinue
    if ($hadConnection) { $env:DB_CONNECTION = $oldConnection } else { Remove-Item Env:DB_CONNECTION -ErrorAction SilentlyContinue }
    if ($hadDatabase) { $env:DB_DATABASE = $oldDatabase } else { Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue }
    if ($hadUrl) { $env:DB_URL = $oldUrl } else { Remove-Item Env:DB_URL -ErrorAction SilentlyContinue }
    php artisan config:clear
    if ($LASTEXITCODE -ne 0 -and $gateExit -eq 0) { $gateExit = $LASTEXITCODE }
}
if ($gateExit -ne 0) { exit $gateExit }
```

Nilai `(null)` dipahami Laravel sebagai URL kosong, termasuk pada PowerShell
5.1 yang menghapus environment variable bila diisi string kosong. Probe
koneksi harus lulus sebelum migration; gagal membersihkan config juga
menghentikan migration.

Pada fixture incremental dalam `ServiceRecordStatusMigrationTest`, jalankan
migration baseline sampai tepat sebelum `2026_09_14_000050`, masukkan fixture
`dibatalkan`, lalu jalankan migration 14 September berurutan. Buktikan row
layanan diarsipkan dan reference inactive. Keberadaan tabel retired tidak
menggagalkan gate selama consumer runtime sudah hilang.
`OperationalSchemaTest` dan `ServiceRecordStatusMigrationTest` tetap dijalankan
dengan konfigurasi test mereka sendiri: yang pertama membuktikan schema fresh,
yang kedua membangun baseline lalu menjalankan migration baru secara langsung
untuk fixture incremental. Jangan mengklaim file `$freshDb` sebagai evidence
incremental atau memaksa PHPUnit yang memakai `:memory:` ke database ini.
Catat hanya nama file sementara serta hasilnya, tanpa credential.


- [ ] **Step 6: Verifikasi MySQL disposable**

Gunakan database MySQL test yang kosong, disposable, dan telah diverifikasi
bukan shared/production. Jalankan `php artisan migrate --force` untuk gate
fresh lalu query `information_schema` untuk memastikan tabel/constraint wajib.
Jalankan juga `ServiceRecordStatusMigrationTest` dengan fixture incremental
yang membangun baseline sebelum migration 14 September. Jangan menjalankan
reset/rollback dan jangan mencetak credential pada output/evidence.

- [ ] **Step 7: Jalankan focused tests**

```powershell
php artisan test tests/Feature/OperationalSchemaTest.php
php artisan test tests/Feature/ServiceRecordStatusMigrationTest.php
php artisan test tests/Feature/FoundationDataTest.php
php vendor/bin/pint --test tests/Feature/OperationalSchemaTest.php
git diff --check
```

- [ ] **Step 8: Commit**

```powershell
git add config/queue.php .env.example tests/Feature/OperationalSchemaTest.php
git commit -m "test: audit kesehatan skema operasional"
```

---

### Task 14: Integrasikan Query Aktif, Laporan, dan Dashboard dengan Arsip/Departure

**Files:**
- Modify: `app/Models/Student.php`
- Modify: `app/Models/BkCase.php`
- Modify: `app/Models/Consultation.php`
- Modify: `app/Services/DashboardService.php`
- Modify: `app/Services/ReportService.php`
- Modify: `app/Services/OperationalReportRecapService.php`
- Modify: `app/Services/WakaDashboardService.php`
- Modify: `app/Services/WakaCaseProjectionQuery.php`
- Modify: `app/Services/WakaMonitoringService.php`
- Modify: `app/Services/WakaPeriodReportService.php`
- Modify: `app/Services/WakaStudentCaseService.php`
- Modify: `app/Services/WakaStudentDepartureService.php`
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `app/Http/Controllers/StudentController.php`
- Modify: `tests/Feature/DashboardTest.php`
- Modify: `tests/Feature/ReportManagementTest.php`
- Modify: `tests/Feature/OperationalReportRecapTest.php`
- Modify: `tests/Feature/StudentDepartureTest.php`
- Modify: `tests/Feature/WakaDashboardTest.php`
- Modify: `tests/Feature/WakaMonitoringTest.php`
- Modify: `tests/Feature/WakaReportPageTest.php`
- Modify: `tests/Feature/WakaStudentDepartureTest.php`

**Interfaces:**
- Consumes: soft delete Task 9, `Student::availableForService()` Task 11, dan service laporan Task 2-6.
- Produces: semua read model operasional konsisten mengecualikan arsip dan tidak menawarkan layanan baru kepada murid resmi keluar, sedangkan Waka tetap dapat membaca daftar/detail proses keluar sesuai kewenangannya.

- [ ] **Step 1: Tulis failing cross-surface test**

```php
public function test_archived_records_and_official_departures_do_not_reappear_cross_surface(): void
{
    [$teacher, $student, $case, $consultation] = $this->operationalFixture();
    $coordinator = $this->userWithRole('koordinator_bk');
    $case->delete();
    $consultation->delete();
    StudentDeparture::query()->create([
        'student_id' => $student->id,
        'departure_type' => 'pindah',
        'status' => 'resmi_keluar',
        'reported_at' => today()->subDay(),
        'effective_date' => today(),
        'recorded_by' => $teacher->id,
        'finalized_by' => $coordinator->id,
        'finalized_at' => now(),
    ]);

    $this->actingAs($teacher)->get(route('cases.index'))->assertDontSee($case->registration_number);
    $this->actingAs($teacher)->get(route('students.index'))->assertDontSee($student->nisn);
    $this->actingAs($teacher)->get(route('reports.index', ['tab' => 'layanan']))
        ->assertDontSee($case->registration_number)
        ->assertDontSee($consultation->registration_number);
    $this->actingAs($teacher)->get(route('cases.create', ['student_id' => $student->id]))
        ->assertForbidden();
}
```

Letakkan test tersebut pada `StudentDepartureTest` dan tambahkan helper:

```php
/** @return array{User, Student, BkCase, Consultation} */
private function operationalFixture(): array
{
    [$teacher, $student] = $this->scopedStudentFixture();
    $case = app(CaseService::class)->createCase([
        'student_id' => $student->id,
        'case_source_id' => $this->reference('case_source', 'temuan_guru_bk')->id,
        'service_field_id' => $this->reference('service_field', 'pribadi')->id,
        'service_date' => '2026-09-10',
        'initial_info' => 'Informasi awal.',
        'initial_action' => 'Asesmen awal.',
    ], $teacher);
    $consultation = app(ConsultationService::class)->create([
        'student_id' => $student->id,
        'service_field_id' => $this->reference('service_field', 'pribadi')->id,
        'status_id' => $this->reference('consultation_status', 'selesai')->id,
        'topic' => 'Persiapan perpindahan',
        'session_date' => '2026-09-11',
        'general_summary' => 'Ringkasan layanan.',
    ], $teacher);

    return [$teacher, $student, $case, $consultation];
}

private function reference(string $category, string $code): ReferenceValue
{
    return ReferenceValue::query()
        ->where('category', $category)
        ->where('code', $code)
        ->firstOrFail();
}
```

Tambahkan assertion Waka tidak melihat arsip pada dashboard, monitoring, atau
rekap; Koordinator masih dapat membuka profil historis melalui route yang sah,
tetapi tidak dapat membuat layanan baru sebagai Guru BK kecuali mempunyai role
dan scope yang sesuai.

Tambahkan assertion terpisah bahwa Waka melihat murid berstatus `dalam_proses`,
`batal`, dan `resmi_keluar` pada halaman proses keluar, termasuk identitas,
kelas, jenis, status, tanggal, ringkasan rekomendasi/keputusan, dan petugas,
tetapi tidak memperoleh tombol atau endpoint mutasi.

- [ ] **Step 2: Jalankan tests dan pastikan gagal**

```powershell
php artisan test tests/Feature/ReportManagementTest.php --filter=archived
php artisan test tests/Feature/WakaMonitoringTest.php --filter=archived
php artisan test tests/Feature/StudentDepartureTest.php --filter=new_service
```

- [ ] **Step 3: Audit seluruh query operational**

Gunakan Eloquent default SoftDeletes untuk kasus/konsultasi dan hapus setiap
`withTrashed()` yang tidak mempunyai alasan historis eksplisit. Pada pilihan
murid untuk membuat kasus, konsultasi, prestasi, atau penugasan baru, gunakan:

```php
Student::query()->availableForService()->professionallyAccessibleTo($actor)
```

Profil historis memakai `Student::query()->accessibleTo($actor)` tanpa
`availableForService()`. Tampilkan badge `Resmi keluar` dan tanggal efektif,
serta sembunyikan tombol mutasi layanan baru.

- [ ] **Step 4: Kunci pipeline laporan/dashboard**

Pastikan aggregate laporan memakai tabel utama tanpa `withTrashed()`. Untuk
murid resmi keluar, histori sebelum `effective_date` tetap dapat muncul pada
laporan periode historis bila actor mempunyai scope histori; record setelah
tanggal keluar tidak boleh dibuat dan tidak boleh muncul.

Waka query selalu mengecualikan archived case/consultation melalui scope model.
Dashboard role-aware menghitung murid aktif menggunakan
`availableForService($periodEnd)` dan tidak menjadikan `student_departures`
sebagai sumber narasi sensitif.

Pengecualian murid resmi keluar dari daftar murid aktif tidak berlaku pada
`WakaStudentDepartureService`: daftar ini sengaja memuat seluruh status proses
keluar untuk kebutuhan kesiswaan dan hanya membaca data yang menjadi bagian
record `student_departures` beserta identitas/kelas terkait.

Pertahankan `StudentController::legacy()` untuk bookmark
`/students/show?nisn={nisn}&tab=...`: policy tetap diperiksa sebelum redirect
ke profile canonical. Jangan mengubah `WakaMonitoringController::legacyHandling`
atau `export`; `/waka/handling-reports` harus tetap redirect ke tab Waka yang
setara dan `/waka/handling-reports/export` tetap menghasilkan CSV hanya untuk
Waka yang berwenang. Tambahkan/pertahankan assertion pada
`StudentProfileTest` dan `WakaMonitoringTest` untuk kontrak tersebut.

- [ ] **Step 5: Verifikasi query consistency**

```powershell
php artisan test tests/Feature/DashboardTest.php
php artisan test tests/Feature/ReportManagementTest.php
php artisan test tests/Feature/OperationalReportRecapTest.php
php artisan test tests/Feature/StudentProfileTest.php
php artisan test tests/Feature/WakaDashboardTest.php
php artisan test tests/Feature/WakaMonitoringTest.php
php artisan test tests/Feature/WakaReportPageTest.php
php artisan test tests/Feature/WakaStudentDepartureTest.php
php vendor/bin/pint --test app/Models/Student.php app/Services/DashboardService.php app/Services/ReportService.php app/Services/OperationalReportRecapService.php app/Services/WakaDashboardService.php app/Services/WakaCaseProjectionQuery.php app/Services/WakaMonitoringService.php app/Services/WakaPeriodReportService.php app/Services/WakaStudentCaseService.php app/Services/WakaStudentDepartureService.php
git diff --check
```

- [ ] **Step 6: Commit**

```powershell
git add app/Models/Student.php app/Models/BkCase.php app/Models/Consultation.php app/Services/DashboardService.php app/Services/ReportService.php app/Services/OperationalReportRecapService.php app/Services/WakaDashboardService.php app/Services/WakaCaseProjectionQuery.php app/Services/WakaMonitoringService.php app/Services/WakaPeriodReportService.php app/Services/WakaStudentCaseService.php app/Services/WakaStudentDepartureService.php app/Http/Controllers/CaseController.php app/Http/Controllers/StudentController.php tests/Feature/DashboardTest.php tests/Feature/ReportManagementTest.php tests/Feature/OperationalReportRecapTest.php tests/Feature/StudentDepartureTest.php tests/Feature/WakaDashboardTest.php tests/Feature/WakaMonitoringTest.php tests/Feature/WakaReportPageTest.php tests/Feature/WakaStudentDepartureTest.php
git commit -m "fix: konsistenkan scope arsip dan murid keluar"
```

---

### Task 15: Full Verification CLI dan Handoff UAT Manual Gabungan

**Files:**
- Create: `docs/testing/2026-09-14-uat-penyederhanaan-operasional.md`
- Modify: `docs/development-log.md`
- Modify: `docs/superpowers/plans/2026-09-14-penyederhanaan-laporan-guru-koordinator.md`

**Interfaces:**
- Consumes: seluruh Task 1-14.
- Produces: evidence gate CLI dan checklist UAT manual gabungan; plan baru
  selesai setelah hasil manual dilaporkan PASS.

- [ ] **Step 1: Jalankan focused feature gate melalui CLI**

```powershell
php artisan test tests/Feature/OperationalReportRecapTest.php
php artisan test tests/Feature/CaseManagementTest.php
php artisan test tests/Feature/ConsultationManagementTest.php
php artisan test tests/Feature/StudentDepartureTest.php
php artisan test tests/Feature/AccountManagementTest.php
php artisan test tests/Feature/AuthenticationTest.php
php artisan test tests/Feature/AdminPasswordRecoveryCommandTest.php
php artisan test tests/Feature/DashboardTest.php
php artisan test tests/Feature/AuthorizationMatrixTest.php
php artisan test tests/Feature/OperationalSchemaTest.php
```

Expected: 0 failure dan 0 error.

- [ ] **Step 2: Jalankan full automated gate melalui CLI**

Jalankan cache command hanya pada konfigurasi worktree/local, bukan aplikasi
shared/production. Bila satu command gagal, tetap jalankan tiga command clear
sebelum keluar dengan error agar cache hasil verifikasi tidak tertinggal.

```powershell
php artisan test
php vendor/bin/pint --test
composer validate --strict
composer audit --locked
npm.cmd audit --audit-level=high
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm.cmd run check:frontend
npm.cmd run build
git diff --check
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Expected: seluruh command exit code 0 dan cache dibersihkan setelah verifikasi.

- [ ] **Step 3: Jalankan retired-feature dan privacy scan**

```powershell
rg -n "Correction|UserNotification|NotificationService|corrections\.|notifications\.|history\." app routes resources tests scripts
git grep -n -E "change_reason|plainTextPassword|temporary_password|initial_info|internal_note|sensitive_content|final_result" -- resources/views/pages/reports resources/views/pages/waka app/Services/OperationalReportRecapService.php app/Services/WakaCaseProjectionQuery.php
git grep -n -i -E "API Data Siswa|https://xxxx|api_key|credential" -- docs app config tests
```

Expected: fitur retired tidak mempunyai consumer runtime dan field sensitif
tidak masuk laporan/Waka. Hasil keyword credential adalah kandidat yang wajib
ditriase: kebijakan generik, placeholder tersamarkan, dan fixture test boleh
tetap ada; nilai credential nyata tidak boleh ada. Evidence mencatat file,
baris, dan keputusan tanpa menyalin nilai kandidat. Bila log lokal perlu dibaca
untuk investigasi, gunakan data test dan keluarkan hasil yang telah disensor.

- [ ] **Step 4: Verifikasi schema dan queue**

Pada SQLite disposable dan MySQL disposable, catat:

```text
Jumlah tabel: dicatat sebagai informasi, bukan gate.
Tabel kandidat retired: boleh tetap ada; tidak mempunyai consumer runtime.
student_departures: unique student_id tersedia.
users: must_change_password, temporary_password_expires_at,
password_changed_at tersedia.
sessions/cache/cache_locks/audit_logs: tersedia.
QUEUE_CONNECTION: sync.
```

Jangan menjalankan reset pada database shared atau menyertakan credential pada
evidence.

- [ ] **Step 5: Siapkan dan serahkan checklist UAT manual gabungan**

Pelaksana UAT adalah pengguna atau tester manusia melalui browser biasa. Agent
hanya menyiapkan akun/data uji, URL awal, expected result, dan checklist; agent
tidak mengoperasikan browser untuk menilai tampilan. Catat status
`PENDING MANUAL` dan jangan menutup plan sebelum hasil manual dikembalikan.

Viewport:

```text
1440 x 900
768 x 1024
390 x 844
```

Skenario:

```text
Guru BK melihat laporan dan data hanya dalam scope.
Koordinator melihat rekap seluruh kelas tanpa membuka isi konsultasi sensitif.
Koreksi Data, Notifikasi, dan Riwayat Perubahan tidak tampil di role mana pun.
Edit data belum selesai langsung membuka form.
Edit data selesai menampilkan konfirmasi dan mewajibkan alasan.
Hapus kasus/konsultasi meminta konfirmasi lalu mengarsipkan.
Guru BK lain, Koordinator, Waka, dan Admin IT gagal edit/arsip direct URL.
Guru BK mencatat proses keluar tanpa menonaktifkan murid.
Koordinator memilih Batal dan murid tetap aktif.
Koordinator memilih Resmi keluar dan layanan baru ditolak sejak effective_date.
Waka melihat daftar/detail seluruh status proses keluar tanpa aksi mutasi.
Sinkronisasi roster tidak mengubah keputusan keluar.
Admin IT membuat/reset akun dan melihat password sementara satu kali.
Pengguna dengan password sementara hanya dapat membuka Ganti Password/Logout.
Password expired ditolak dan session lama telah putus.
Dashboard menampilkan panel role-aware tanpa aktivitas audit.
Tidak ada horizontal overflow dan seluruh focus state terlihat.
```

- [ ] **Step 6: Terima hasil manual dan tutup plan**

Setelah pelaksana manual mengirim hasil, isi UAT dengan nama/inisial tester,
tanggal, browser dan versi, branch/SHA, database engine, jumlah tabel, jumlah
test/assertion CLI, hasil tiap role, viewport, catatan temuan, privacy scan,
build, dan status PASS/FAIL. Jika ada skenario FAIL, perbaiki melalui task
terkait, jalankan ulang gate CLI yang terdampak, dan minta pengujian ulang hanya
untuk skenario manual terkait. Tandai checkbox plan dan pindahkan tracker ke
Completed pada `docs/development-log.md` hanya setelah seluruh CLI dan manual
gate PASS.

- [ ] **Step 7: Commit evidence**

```powershell
git add docs/testing/2026-09-14-uat-penyederhanaan-operasional.md docs/development-log.md docs/superpowers/plans/2026-09-14-penyederhanaan-laporan-guru-koordinator.md
git commit -m "docs: catat verifikasi penyederhanaan Ruang BK"
```

---

## Urutan Eksekusi

```text
Task 1 Requirement contract
    -> Task 2 Filter and safe presentation foundation
    -> Task 3 Violation recap
    -> Task 4 Service recap
    -> Task 5 Achievement recap
    -> Task 6 HTTP, export, and UI integration
    -> Task 7 CLI report verification and manual UAT handoff
    -> Task 8 Operational requirement and API boundaries
    -> Task 9 Terminal edit, archive, and status simplification
    -> Task 10 Retire correction, notification, history, and audit feed UI
    -> Task 11 Student departure process
    -> Task 12 Temporary passwords and admin recovery
    -> Task 13 Schema health and retired-table audit
    -> Task 14 Cross-surface scope integration
    -> Task 15 Combined CLI verification and manual UAT handoff
```

Task 3, Task 4, dan Task 5 menyentuh service yang sama, sehingga eksekusi pada satu branch harus berurutan. Jangan menjalankan ketiganya secara paralel pada worktree yang sama.

Task 9-14 juga harus berurutan. Task 13 hanya mengaudit tabel kandidat retired
dan tidak melakukan drop fisik. Task 14 baru boleh mengubah seluruh
query baca setelah lifecycle dan schema feature pada Task 9, 11, 12, dan 13
lulus focused gate.

## Definition of Done

- `/reports` menampilkan tiga tab, bukan tujuh kartu.
- Setiap tab memakai rekap satu baris per murid atau identitas sementara yang sah.
- Pencarian, kelas, tahun ajaran, periode, dan filter Guru BK bekerja sesuai kontrak.
- Guru BK dan Koordinator menerima scope yang benar.
- Desktop memakai tabel dan mobile memakai kartu tanpa horizontal overflow halaman.
- CSV dan cetak memakai dataset tab aktif.
- Endpoint legacy tetap berfungsi.
- Tidak ada dependency framework tabel baru.
- Tidak ada data sensitif pada HTML, CSV, error, atau log baru.
- Koreksi Data, Notifikasi, Riwayat Perubahan, Aktivitas Terbaru, dan Batalkan
  Kasus tidak tersedia pada UI/runtime.
- Kasus dan konsultasi selesai dapat diedit owner dengan alasan; tombol Hapus
  mengarsipkan dan seluruh direct URL tetap dilindungi policy.
- Proses keluar murid memakai satu row dan hanya keputusan Koordinator
  `resmi_keluar` yang menghentikan layanan baru serta memulai retensi.
- API roster/pelanggaran tidak mengubah proses keluar murid.
- Password sementara unik, expiry, wajib ganti, session invalidation, dan
  command pemulihan Admin IT berfungsi tanpa password masuk log/audit.
- Skema mempunyai satu sumber kebenaran, constraint domain wajib, tidak memiliki
  tabel rekap/flag duplikat, dan tabel kandidat retired tidak mempunyai consumer
  runtime; jumlah tabel tidak menjadi gate dan penghapusan fisik ditunda.
- Tidak ada tabel rekap UI, flag keluar murid duplikat, atau tabel alasan edit.
- Focused tests, full suite, Pint, cache, frontend checker, build, SQLite/MySQL
  query gate, privacy scan, dependency scan, dan security scan lulus melalui
  CLI; seluruh checklist tampilan/interaksi mendapat hasil PASS dari UAT manual
  pengguna atau tester manusia.
