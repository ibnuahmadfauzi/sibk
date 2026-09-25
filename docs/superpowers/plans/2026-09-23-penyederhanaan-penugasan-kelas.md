# Penyederhanaan Penugasan Kelas Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menjadikan `/assignments/classes` sebagai satu-satunya halaman pengelolaan penugasan Guru BK per kelas, dengan konteks tahun sistem, modal ringkas, lifecycle otomatis, aktivasi tahun ajaran, dan tanpa Nomor SK.

**Architecture:** `AssignmentService` tetap menjadi jalur transaksi penugasan dan menyediakan data halaman agar controller tipis. Daftar berbasis `Classroom`, modal Bootstrap dipakai ulang untuk semua row, sedangkan migration forward-only menghapus `decision_number` beserta seluruh consumer aktif.

**Tech Stack:** PHP 8.3, Laravel, Eloquent, Blade, Bootstrap, JavaScript native, MySQL/SQLite migration.

**Spec:** `docs/superpowers/specs/2026-09-23-penyederhanaan-penugasan-kelas-design.md`

## Global Constraints

- Hanya Koordinator BK dapat melihat atau mengubah penugasan.
- Request browser hanya menerima `classroom_id` dan `user_id`.
- Tahun ajaran serta tanggal efektif diturunkan dan divalidasi server.
- Tidak ada dependency, menu aktivasi, pagination, atau halaman form baru.
- Migration forward-only; migration pembuat tabel lama tidak diubah.
- UI Bahasa Indonesia, memakai istilah `murid`, dan tag panjang ditulis multiline.
- Jangan menjalankan test, Pint, checker frontend, build, atau migration tanpa perintah pengguna.

## Review Focus

- Manipulasi `academic_year_id` atau pasangan kelas lintas tahun harus ditolak.
- Dua penyimpanan bersamaan tidak boleh membuat assignment overlap.
- Guru yang sama harus menjadi no-op tanpa write/audit baru.
- Penggantian assignment terjadwal harus memperbarui record yang sama.
- Penugasan pertama yang terlambat pada tahun aktif harus mulai hari penyimpanan.

---

### Task 1: Bangun data halaman berbasis kelas

**Files:**
- Modify: `app/Services/AssignmentService.php`
- Modify: `app/Http/Controllers/AssignmentController.php`
- Modify: `resources/views/pages/assignments/classes/index.blade.php`
- Delete: `resources/views/pages/assignments/classes/manage.blade.php`
- Modify: `resources/js/app-dashboard.js`

**Interfaces:**
- Produces: `AssignmentService::pageData(User $actor, array $filters): array`.
- Produces: row kelas dengan `classroom`, `student_count`, `assignment`, dan `is_assigned`.
- Preserves: route name `assignments.classes.manage` sebagai redirect kompatibilitas.

- [ ] **Step 1: Tentukan konteks tahun pada service**

Buat helper private yang memilih tahun aktif; bila tidak ada, pilih satu tahun persiapan nonaktif, belum berakhir, mempunyai kelas aktif, dan `starts_on` terdekat. Parameter `academic_year_id` hanya boleh menunjuk salah satu konteks yang diizinkan.

Tanggal konteks:

```php
$contextDate = $year->is_active
    ? today()->startOfDay()
    : $year->starts_on->startOfDay();
```

- [ ] **Step 2: Query seluruh kelas dan assignment konteks**

Query `Classroom::active()` pada tahun konteks, hitung membership `activeOn($contextDate)` yang muridnya aktif, eager-load assignment yang `effectiveOn($contextDate)`, filter pencarian/status allowlist, lalu urutkan `name`.

Service juga menghasilkan jumlah total/ditugaskan, daftar Guru BK aktif, ringkasan maksimal tiga kelas per Guru BK, kandidat tahun persiapan, dan `activationReadiness()`.

- [ ] **Step 3: Tipiskan controller**

`index()` hanya mengotorisasi, memanggil `pageData()`, dan me-render view. `manage()` memvalidasi konteks lewat service lalu redirect:

```php
return redirect()->route('assignments.classes.index', $query);
```

- [ ] **Step 4: Ganti view menjadi satu tabel dan satu modal**

Tabel memuat Kelas, Murid, Guru BK, Status, Aksi. Tambahkan pencarian kelas, filter status, ringkasan jumlah, banner tahun persiapan, readiness ringkas, dan aksi aktivasi pada halaman yang sama.

Gunakan satu modal Bootstrap dengan hidden `classroom_id`, select `user_id`, token CSRF, dan tombol Batal/Simpan. Tombol row membawa `data-classroom-id`, `data-classroom-name`, dan `data-user-id`.

- [ ] **Step 5: Isi modal dengan handler kecil**

Tambahkan handler pada `app-dashboard.js` yang membaca `event.relatedTarget`, mengisi nama kelas/hidden input/select, dan membuka ulang modal bila container mempunyai penanda error server. Jangan membuat file JavaScript baru.

- [ ] **Step 6: Pemeriksaan statis dan commit**

```powershell
rg -n "assignments\.classes\.manage|decision_number|effective_date|effective_until" app/Http/Controllers/AssignmentController.php resources/views/pages/assignments resources/js/app-dashboard.js
git diff --check
git add app/Services/AssignmentService.php app/Http/Controllers/AssignmentController.php resources/views/pages/assignments/classes/index.blade.php resources/js/app-dashboard.js
git add -u resources/views/pages/assignments/classes/manage.blade.php
git commit -m "feat: satukan halaman penugasan kelas"
```

### Task 2: Otomatiskan lifecycle penugasan

**Files:**
- Modify: `app/Http/Requests/StoreClassAssignmentRequest.php`
- Modify: `app/Services/AssignmentService.php`
- Modify: `app/Http/Controllers/AssignmentController.php`
- Modify: `app/Http/Controllers/AcademicYearActivationController.php`
- Modify: `tests/Feature/AssignmentManagementTest.php`
- Modify: `tests/Feature/AcademicYearRolloverTest.php`

**Interfaces:**
- Consumes: `array{classroom_id:int, user_id:int}`.
- Produces: `AssignmentService::assignClass(array $data, User $actor): TeacherAssignment`.

- [ ] **Step 1: Kecilkan Form Request**

Validasi hanya:

```php
return [
    'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
    'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
];
```

Pertahankan otorisasi `TeacherAssignmentPolicy::create()`.

- [ ] **Step 2: Turunkan tahun dan tanggal dari kelas**

Di dalam transaksi, lock Guru BK, kelas, tahun kelas, dan seluruh assignment kelas/tahun. Tolak kelas nonaktif, tahun berakhir, Guru BK nonaktif/non-role, serta konteks tahun yang tidak diizinkan.

Gunakan `starts_on` untuk tahun yang belum dimulai dan `today()` untuk tahun aktif. Jangan membaca tanggal atau tahun dari request.

- [ ] **Step 3: Terapkan lima cabang lifecycle**

Implementasikan urutan:

```text
guru sama pada assignment konteks -> return tanpa write/audit
assignment terjadwal belum berlaku -> update user_id + assigned_by pada record yang sama
assignment aktif dan guru berubah -> tutup kemarin, buat record hari ini
belum ada assignment -> buat pada starts_on atau hari ini
overlap/tahun berakhir -> ValidationException
```

Create/update/close menggunakan audit sebelum/sesudah; no-op tidak menulis audit.

- [ ] **Step 4: Kembalikan redirect ke halaman tunggal**

Setelah simpan atau aktivasi, redirect ke `assignments.classes.index`. Validasi gagal harus mengembalikan `classroom_id` dan `user_id` agar modal yang sama terbuka kembali.

- [ ] **Step 5: Pemeriksaan statis dan commit**

Selaraskan assertion existing dengan payload dua field, no-op, update terjadwal,
pergantian aktif, dan redirect ke halaman tunggal. Jangan menjalankan test.

```powershell
rg -n "academic_year_id|effective_date|effective_until|decision_number" app/Http/Requests/StoreClassAssignmentRequest.php app/Services/AssignmentService.php app/Http/Controllers/AssignmentController.php
git diff --check
git add app/Http/Requests/StoreClassAssignmentRequest.php app/Services/AssignmentService.php app/Http/Controllers/AssignmentController.php app/Http/Controllers/AcademicYearActivationController.php tests/Feature/AssignmentManagementTest.php tests/Feature/AcademicYearRolloverTest.php
git commit -m "feat: otomatisasi lifecycle penugasan kelas"
```

### Task 3: Hapus Nomor SK dari schema dan consumer

**Files:**
- Create: `database/migrations/2026_09_23_000100_drop_decision_number_from_teacher_assignments.php`
- Modify: `app/Models/TeacherAssignment.php`
- Modify: `app/Services/AssignmentService.php`
- Modify: `database/seeders/DummyCaseAndServiceSeeder.php`
- Modify: `database/seeders/ReportPreviewSeeder.php`
- Modify: `tests/Feature/AssignmentManagementTest.php`
- Modify: `tests/Feature/AcademicYearRolloverTest.php`
- Modify: `tests/Feature/AchievementManagementTest.php`
- Modify: `tests/Feature/CaseManagementTest.php`
- Modify: `tests/Feature/ConsultationManagementTest.php`
- Modify: `tests/Feature/DashboardTest.php`
- Modify: `tests/Feature/DelayedDapodikPreparationTest.php`
- Modify: `tests/Feature/DapodikSyncTest.php`
- Modify: `tests/Feature/OperationalReportRecapTest.php`
- Modify: `tests/Feature/ReportManagementTest.php`
- Modify: `tests/Feature/StudentDepartureTest.php`
- Modify: `tests/Feature/StudentProfileTest.php`

**Interfaces:**
- Removes: column and active model/request/service dependency `decision_number`.
- Preserves: old audit JSON values unchanged.

- [ ] **Step 1: Tambahkan migration forward-only**

```php
public function up(): void
{
    Schema::table('teacher_assignments', function (Blueprint $table): void {
        $table->dropColumn('decision_number');
    });
}

public function down(): void
{
    Schema::table('teacher_assignments', function (Blueprint $table): void {
        $table->string('decision_number', 150)->nullable();
    });
}
```

- [ ] **Step 2: Hapus consumer runtime**

Hapus field dari `$fillable`, docblock payload, create array, snapshot baru, seeder demo, dan seeder preview. Jangan mengubah migration pembuat tabel lama atau audit record lama.

- [ ] **Step 3: Selaraskan fixture test existing**

Hapus key `decision_number` dari seluruh factory array inline dan assertion
schema aktif. Ubah assertion request Assignment menjadi hanya `classroom_id`
serta `user_id`. Jangan menambah suite baru dan jangan menjalankan test.

- [ ] **Step 4: Pemeriksaan statis dan commit**

```powershell
rg -n "decision_number" app database/seeders resources/views
git diff --check
git add database/migrations/2026_09_23_000100_drop_decision_number_from_teacher_assignments.php app/Models/TeacherAssignment.php app/Services/AssignmentService.php database/seeders/DummyCaseAndServiceSeeder.php database/seeders/ReportPreviewSeeder.php tests/Feature/AssignmentManagementTest.php tests/Feature/AcademicYearRolloverTest.php tests/Feature/AchievementManagementTest.php tests/Feature/CaseManagementTest.php tests/Feature/ConsultationManagementTest.php tests/Feature/DashboardTest.php tests/Feature/DelayedDapodikPreparationTest.php tests/Feature/DapodikSyncTest.php tests/Feature/OperationalReportRecapTest.php tests/Feature/ReportManagementTest.php tests/Feature/StudentDepartureTest.php tests/Feature/StudentProfileTest.php
git commit -m "refactor: hapus nomor sk penugasan"
```

### Task 4: Selaraskan dokumentasi canonical

**Files:**
- Modify only if owned facts change:
  - `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
  - `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
  - `docs/api-contract.md`

**Ownership:**
- PRD hanya untuk keputusan produk.
- SRS untuk perilaku sistem, business rule, authorization, invariant, dan acceptance criteria.
- API Contract hanya untuk interface teknis dan mereferensikan requirement ID SRS bila relevan.
- Jangan membuat atau memperbarui frontend map, authorization matrix, current work, atau development log.

- [ ] **Step 1: Perbarui keputusan produk bila berubah**

Hapus penyebutan Nomor SK/dasar keputusan dari PRD hanya bila perubahan tersebut merupakan keputusan produk.

- [ ] **Step 2: Perbarui perilaku sistem**

Selaraskan requirement `ASN-*` yang benar-benar berubah, termasuk lifecycle penugasan dan field yang ditentukan server.

- [ ] **Step 3: Perbarui interface teknis**

Selaraskan request/endpoint penugasan pada API Contract dan referensikan requirement `ASN-*` tanpa mengulang business rule lengkap.

- [ ] **Step 4: Verifikasi dan commit**

```powershell
rg -n "Nomor SK|dasar keputusan|Tanggal Mulai|assignments/classes/manage" docs/requirements docs/api-contract.md
git diff --check
git add docs/requirements/PRD_Aplikasi_BK_v1.1.md docs/requirements/SRS_Aplikasi_BK_v1.1.md docs/api-contract.md
git commit -m "docs: selaraskan penugasan kelas sederhana"
```

Test, migration execution, dan gate repository sengaja tidak dijalankan sampai pengguna memberi perintah.
