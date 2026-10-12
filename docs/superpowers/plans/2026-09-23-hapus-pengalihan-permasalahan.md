# Hapus Pengalihan Permasalahan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menghapus seluruh permukaan aplikasi untuk pengalihan pemilik kasus tanpa menghapus owner awal, histori existing, atau scope akses kasus.

**Architecture:** Hapus route, controller action, request, view, menu, policy action, dan mutasi service yang khusus untuk transfer. Pertahankan `case_assignments` serta `CaseAssignment` karena pembuatan kasus dan otorisasi masih bergantung padanya.

**Tech Stack:** PHP 8.3, Laravel routing/policy/service, Blade.

**Spec:** `docs/superpowers/specs/2026-09-23-penyederhanaan-penugasan-kelas-design.md`

## Global Constraints

- Jangan membuat migration untuk `case_assignments`.
- Jangan mengubah atau menghapus audit lama `case.transferred`.
- Kasus baru tetap mendapat satu owner dari `CaseService::createCase()`.
- Bantuan Guru BK lain tidak direkam sebagai perpindahan owner.
- Jangan menjalankan test, Pint, checker frontend, atau build tanpa perintah pengguna.

## Review Focus

- Pembuatan kasus harus tetap menulis owner awal.
- `accessibleTo()` dan `hasActiveOwnerFor()` harus tetap bekerja memakai `case_assignments`.
- Endpoint GET dan POST pengalihan harus tidak terdaftar.
- Aktivasi tahun ajaran harus tetap hanya dapat dilakukan Koordinator.
- Tidak boleh ada tautan mati menuju halaman Pengalihan Permasalahan.

---

### Task 1: Hapus endpoint dan UI pengalihan

**Files:**
- Modify: `routes/web.php`
- Modify: `resources/views/components/sidebar.blade.php`
- Delete: `resources/views/pages/assignments/cases/index.blade.php`
- Modify: `app/Http/Controllers/AssignmentController.php`
- Delete: `app/Http/Requests/AssignCaseRequest.php`
- Modify: `app/Http/Controllers/LegacyPreviewController.php`
- Modify: `tests/Feature/AuthorizationMatrixTest.php`

**Interfaces:**
- Removes: route names `cases.assign`, `assignments.cases.index`, dan `fixtures.assignments.cases.index`.
- Preserves: class-assignment routes and academic-year activation route.

- [ ] **Step 1: Hapus tiga route pengalihan**

Hapus route POST `/cases/{case}/assign`, GET `/assignments/cases`, dan bookmark `/_preview/assignments/cases` beserta import/route yang tidak lagi dipakai.

- [ ] **Step 2: Hapus menu dan halaman**

Hapus blok `@can('manageCaseAssignments')` untuk “Pengalihan Permasalahan”, lalu hapus Blade halaman kasus assignment.

- [ ] **Step 3: Hapus action controller dan request**

Hapus `caseIndex()`, `assignCase()`, import `AssignCaseRequest`, `BkCase`, `Gate`, serta dependency lain yang menjadi tidak terpakai. Hapus request class seluruhnya.

- [ ] **Step 4: Bersihkan fixture preview**

Hapus destination `assignments.cases.index` dari `LegacyPreviewController` dan parameter fixture terkait.
Hapus `/assignments/cases` dari matriks route feature test karena endpoint tidak
lagi menjadi permukaan otorisasi. Jangan menjalankan test.

- [ ] **Step 5: Pemeriksaan statis dan commit**

```powershell
rg -n "cases\.assign|assignments\.cases|AssignCaseRequest|Pengalihan Permasalahan" routes app resources
git diff --check
git add routes/web.php resources/views/components/sidebar.blade.php app/Http/Controllers/AssignmentController.php app/Http/Controllers/LegacyPreviewController.php tests/Feature/AuthorizationMatrixTest.php
git add -u app/Http/Requests/AssignCaseRequest.php resources/views/pages/assignments/cases/index.blade.php
git commit -m "refactor: hapus halaman pengalihan permasalahan"
```

### Task 2: Hapus mutasi dan otorisasi khusus transfer

**Files:**
- Modify: `app/Services/AssignmentService.php`
- Modify: `app/Policies/CasePolicy.php`
- Modify: `app/Http/Controllers/CaseController.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Http/Controllers/AcademicYearActivationController.php`
- Modify: `app/Services/AcademicYearPreparationService.php`

**Interfaces:**
- Removes: `AssignmentService::assignCase()` dan `CasePolicy::assign()`.
- Reuses: `TeacherAssignmentPolicy::create()` untuk kewenangan aktivasi Koordinator.

- [ ] **Step 1: Hapus mutasi transfer**

Hapus `assignCase()`, `caseAssignmentSnapshot()`, serta import `BkCase`, `CaseAssignment`, dan `ServiceRecordStatus` yang hanya dipakai transfer. Jangan menyentuh `CaseService::createCase()`.

- [ ] **Step 2: Hapus policy action kasus**

Hapus `CasePolicy::assign()` dan `canAssignCase` dari payload `CaseController::show()`.

- [ ] **Step 3: Ganti Gate bernama lama pada aktivasi**

Ganti otorisasi `manageCaseAssignments` pada controller/service aktivasi dengan policy penugasan kelas:

```php
Gate::forUser($actor)->authorize('create', TeacherAssignment::class);
```

Setelah tidak ada consumer, hapus definisi `manageCaseAssignments` dari `AppServiceProvider`.

- [ ] **Step 4: Pemeriksaan statis dan commit**

```powershell
rg -n "assignCase|caseAssignmentSnapshot|canAssignCase|manageCaseAssignments|case\.transferred" app resources routes
git diff --check
git add app/Services/AssignmentService.php app/Policies/CasePolicy.php app/Http/Controllers/CaseController.php app/Providers/AppServiceProvider.php app/Http/Controllers/AcademicYearActivationController.php app/Services/AcademicYearPreparationService.php
git commit -m "refactor: hapus mutasi pengalihan kasus"
```

Hasil pencarian boleh menyisakan `case.transferred` hanya pada dokumentasi/audit historis, bukan jalur write aktif.

### Task 3: Amendemen kontrak dan checkpoint

**Files:**
- Modify: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Modify: `docs/api-contract.md`
- Modify: `docs/frontend-map.md`
- Modify: `docs/testing/authorization-matrix.md`
- Modify: `docs/current-work.md`
- Modify: `docs/development-log.md`

**Interfaces:**
- Removes: ASN-04/ASN-05 dan kontrak endpoint pengalihan.
- Preserves: aturan satu owner awal dan histori existing.

- [ ] **Step 1: Hapus requirement aktif pengalihan**

Amendemen PRD/SRS agar penanganan offline oleh Guru BK lain tidak menghasilkan transfer aplikasi dan pencatatan resmi dilakukan Guru BK pengampu.

- [ ] **Step 2: Hapus peta halaman dan endpoint**

Hapus PG-403, endpoint pengalihan, serta baris matriks akses `/assignments/cases`. Jangan menghapus aturan otorisasi owner kasus existing.

- [ ] **Step 3: Perbarui handoff dan commit**

```powershell
rg -n "ASN-04|ASN-05|assignments/cases|cases/\{case\}/assign|Pengalihan Permasalahan" docs
git diff --check
git add docs/requirements/PRD_Aplikasi_BK_v1.1.md docs/requirements/SRS_Aplikasi_BK_v1.1.md docs/api-contract.md docs/frontend-map.md docs/testing/authorization-matrix.md docs/current-work.md docs/development-log.md
git commit -m "docs: hapus kontrak pengalihan permasalahan"
```

Test dan gate repository sengaja tidak dijalankan sampai pengguna memberi perintah.
