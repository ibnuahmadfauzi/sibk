# Koreksi Tahun Ajaran Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) for tracking.

**Goal:** Admin IT dapat menghapus tahun Persiapan yang benar-benar kosong, dan Koordinator BK dapat mengembalikan tahun aktif sebelumnya hanya sebelum ada aktivitas operasional setelah aktivasi.

**Architecture:** Pertahankan dua state tahun yang ada (`is_active` dan `activated_at`), tanpa status atau tabel baru. Semua pemeriksaan dan perubahan status dilakukan di `AcademicYearPreparationService` dalam transaksi; controller hanya mengotorisasi, memanggil service, lalu mengarahkan kembali. Audit sebelum/sesudah tetap memakai `AuditService`.

**Tech Stack:** Laravel/PHP 8.3, Blade, Bootstrap, PHPUnit.

**Spec:** `docs/requirements/SRS_Aplikasi_BK_v1.1.md` MD-05, MD-08, MD-09; keputusan pengguna 25 September 2026 tentang draf kosong dan pengembalian tahun sebelumnya. Perbarui SRS dan `docs/api-contract.md` pada Task 1 sebelum implementasi.

## Global Constraints

- Hanya Admin IT yang boleh menghapus draf Persiapan kosong; hanya Koordinator BK yang boleh mengembalikan tahun sebelumnya. Periksa izin di server dan service.
- Tahun yang pernah aktif tidak boleh dihapus. Roster, penugasan, catatan, dan audit tidak boleh ikut dihapus.
- Tepat satu tahun aktif setelah rollback; bila tidak ada pendahulu yang dapat dipastikan, tolak aksi.
- Migrasi forward-only dan database bersama tidak direset. Plan ini tidak membutuhkan migrasi.
- Gunakan Bahasa Indonesia untuk UI, dokumentasi, dan commit.
- Jalankan hanya pemeriksaan terarah sebagai agent; perintah berat mengikuti kebijakan `AGENTS.md` dan dijalankan pengguna.

## Review Focus

- Permintaan bersamaan dengan impor roster/aktivasi: kunci baris tahun dan periksa ulang seluruh syarat di dalam transaksi.
- Tahun Persiapan sudah memiliki satu rombel atau relasi lain: penghapusan ditolak dan tidak ada data yang terhapus.
- Tahun baru memiliki catatan yang sudah soft-delete: rollback tetap ditolak.
- Aktivitas yang tidak menyimpan `academic_year_id` (prestasi/proses keluar murid): rollback ditolak bila terjadi setelah aktivasi.
- Tahun pendahulu tidak unik, tidak siap, atau tidak ada: rollback ditolak tanpa mengubah tahun aktif.

---

### Task 1: Kontrak perilaku dan identifikasi tahun pendahulu

**Files:**
- Modify: `docs/requirements/SRS_Aplikasi_BK_v1.1.md` pada MD-05/MD-08.
- Modify: `docs/api-contract.md` pada kontrak tahun ajaran.

- [ ] **Step 1:** Dokumentasikan dua aksi: `Hapus draf` hanya untuk Persiapan tanpa relasi; `Kembalikan tahun sebelumnya` hanya untuk tahun aktif yang memiliki tepat satu pendahulu. Tahun pendahulu adalah tahun arsip dengan `activated_at` terbesar yang lebih kecil dari `activated_at` tahun aktif. Jika waktu sama, kosong, atau data tidak unik, tolak dan minta koreksi Admin IT di luar aksi otomatis. Setelah rollback, tahun yang dilepas tetap arsip dengan `activated_at` dan seluruh datanya tetap ada.
- [ ] **Step 2:** Tinjau diff dua dokumen: larangan membatalkan tahun yang sudah digunakan, peran Admin IT/Koordinator, dan aturan satu tahun aktif harus tertulis eksplisit.

### Task 2: Hapus draf Persiapan yang kosong

**Files:**
- Modify: `app/Services/AcademicYearPreparationService.php`.
- Modify: `app/Http/Controllers/Admin/AcademicYearPreparationController.php`.
- Modify: `routes/web.php`.
- Modify: `resources/views/pages/data-master/_academic-year-preparation.blade.php`.
- Test: `tests/Feature/AcademicYearRolloverTest.php`.

- [ ] **Step 1:** Tambahkan test Admin IT berhasil menghapus draf kosong; Koordinator/Guru BK ditolak; tahun aktif/arsip dan draf dengan `classrooms`, `student_class_memberships`, `teacher_assignments`, kasus, atau konsultasi ditolak. Audit `academic_year.preparation_deleted` menyimpan snapshot tahun sebelum penghapusan.
- [ ] **Step 2:** Tambahkan `deleteEmptyPreparationYear(AcademicYear $year, User $actor): void` di service. Otorisasi `manageDataMaster`, `lockForUpdate`, cek `is_active === false`, `activated_at === null`, dan keberadaan seluruh relasi tersebut sebelum audit dan `delete()`. Biarkan pembatasan foreign key menolak referensi lain yang belum tercakup; jangan melakukan cascade.

```php
if ($year->is_active || $year->activated_at !== null || $year->classrooms()->exists()) {
    throw ValidationException::withMessages(['academic_year' => 'Tahun ajaran ini tidak dapat dihapus.']);
}
```

- [ ] **Step 3:** Tambahkan route DELETE dan tombol `Hapus draf` hanya pada kartu Persiapan kosong di Data Master. Gunakan modal konfirmasi Bootstrap yang menyebut nama tahun, lalu tampilkan pesan sukses ringkas. Jangan pakai `window.confirm`.
- [ ] **Step 4:** Jalankan test terarah Task 2 secara manual sesuai `AGENTS.md`; periksa otorisasi dan audit sebelum lanjut.

### Task 3: Kembalikan tahun aktif sebelumnya

**Files:**
- Modify: `app/Services/AcademicYearPreparationService.php`.
- Modify: `app/Http/Controllers/AcademicYearActivationController.php`.
- Modify: `routes/web.php`.
- Modify: `resources/views/pages/assignments/classes/index.blade.php`.
- Test: `tests/Feature/AcademicYearRolloverTest.php`.

- [ ] **Step 1:** Tambahkan test fixture tiga tahun dengan waktu aktivasi berbeda. Pastikan kandidat adalah arsip tepat sebelum tahun aktif; waktu sama, kandidat tidak ada, atau lebih dari satu tahun aktif ditolak. Test Koordinator berhasil mengembalikan pendahulu dalam satu transaksi dan dua audit baru (`academic_year.activation_reverted` untuk tahun yang dilepas dan `academic_year.reactivated` untuk pendahulu). Assert `activated_at` tahun yang dilepas tetap ada dan tidak ada dua tahun aktif.
- [ ] **Step 2:** Tambahkan test penolakan untuk aktor lain, pendahulu tidak siap, kasus atau konsultasi termasuk soft-delete pada tahun baru, serta perubahan kasus, konsultasi, prestasi, atau proses keluar murid setelah aktivasi meski tidak mengacu pada tahun baru.
- [ ] **Step 3:** Tambahkan `restorePreviousAcademicYear(AcademicYear $current, User $actor): AcademicYear` di service. Di dalam transaksi, kunci tahun aktif dan kandidat pendahulu; pastikan satu-satunya tahun aktif adalah `$current`, temukan pendahulu tepat sebelumnya, jalankan pemeriksaan readiness yang sudah ada, lalu cek aktivitas operasional. Untuk kasus/konsultasi gunakan `withTrashed()` dan blokir bila terkait tahun baru atau `created_at`/`updated_at` sejak aktivasi; untuk prestasi gunakan `withTrashed()`, dan untuk proses keluar murid gunakan waktu perubahan yang sama. Bila lolos, set current `is_active=false`, previous `is_active=true`, lalu tulis audit; jangan ubah `activated_at`/`activated_by` historis.

- [ ] **Step 4:** Tambahkan route POST dan tombol `Kembalikan tahun sebelumnya` pada panel Kesiapan Aktivasi hanya untuk Koordinator ketika kandidat dapat dipastikan. Modal menyebut kedua nama tahun serta konsekuensi perubahan akses. Selalu ulangi pemeriksaan di service saat POST; jangan mengandalkan kondisi tombol.
- [ ] **Step 5:** Jalankan `php artisan test --filter=AcademicYearRollover` dan `php artisan test --filter=AssignmentManagement` secara manual sesuai `AGENTS.md`. Jalankan `php vendor/bin/pint --test` untuk file terkait bila tersedia, lalu `git diff --check`; tinjau diff dan commit dalam Bahasa Indonesia.

## Batas keputusan

Tidak ada status `Dibatalkan`, penghapusan roster, atau rollback otomatis ke tahun yang tidak dapat dipastikan. Aktivitas operasional setelah aktivasi sengaja memblokir rollback; koreksi kasus tersebut memerlukan penanganan data dan keputusan Admin IT/Koordinator secara terpisah.

## Checkpoint implementasi 25 September 2026

Implementasi route, service, UI, audit, dan kontrak sudah dibuat pada worktree Penugasan Kelas yang sama. Lima test terarah untuk hapus draf, rollback, otorisasi, aktivitas, dan waktu pendahulu ambigu lulus. Pint terarah dan `git diff --check` lulus. Dua test lama yang merender halaman tertahan izin tulis `storage/framework/views` pada lingkungan agent; tidak ada perubahan aplikasi untuk mengatasi batas lingkungan tersebut. Build frontend dan suite penuh mengikuti kebijakan `AGENTS.md` untuk dijalankan pengguna.

Lanjutan 25 September: form Admin IT tidak lagi meminta tanggal mulai/selesai. Sistem menghitung rentang internal Juli–Juni dari nama dua tahun berurutan agar impor roster dan laporan yang sudah ada tetap berfungsi. Tombol Koordinator diberi label `Batalkan aktivasi`; pembatalan tetap mengembalikan tahun sebelumnya sesuai guard rollback.
