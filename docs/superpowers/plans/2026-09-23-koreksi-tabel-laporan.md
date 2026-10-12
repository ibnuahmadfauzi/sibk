# Koreksi Tabel Laporan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menampilkan Hasil sebagai satu-satunya narasi utama pada tabel laporan Guru BK/Koordinator, membuka Latar Belakang Masalah dan Penanganan melalui satu ikon kaca pembesar, serta menggabungkan Sumber dan Tindak Lanjut pada Keterangan dokumen Permasalahan.

**Architecture:** Pertahankan `OperationalReportRecapService` sebagai satu sumber mapping catatan. Tambahkan nilai Keterangan khusus dokumen tanpa mengubah proyeksi Waka, lalu sederhanakan dua partial daftar dan satu handler JavaScript yang sudah ada.

**Tech Stack:** PHP 8.3, Laravel, Blade, Bootstrap, JavaScript native, SCSS, PhpSpreadsheet.

**Spec:** `docs/superpowers/specs/2026-09-23-penyederhanaan-penugasan-kelas-design.md`

## Global Constraints

- Jangan menambah dependency atau membuat komponen JavaScript baru.
- UI memakai Bahasa Indonesia dan istilah `murid`.
- Tag Blade/HTML panjang ditulis multiline agar mudah diperiksa.
- Preview/PDF/Excel tetap tujuh kolom; hanya isi Keterangan yang berubah.
- Proyeksi dan kemampuan Waka tidak berubah.
- Riwayat Tindak Lanjut tidak dibuat; gunakan `follow_up_type_id` terbaru.
- Jangan menjalankan test, Pint, checker frontend, atau build tanpa perintah pengguna.

## Review Focus

- Permasalahan tanpa Sumber atau Tindak Lanjut harus menampilkan tanda `—`, bukan error.
- Konsultasi harus tetap memakai Keterangan `Selesai`.
- Waka tidak boleh menerima `problem`, `handling`, URL aksi, atau field dokumen baru.
- Dua tombol detail lama harus benar-benar menjadi satu tombol yang dapat dipakai keyboard.
- Hasil kosong tetap ditampilkan sebagai `—` dan row detail dapat dibuka/tutup.

---

### Task 1: Pisahkan nilai UI dan Keterangan dokumen

**Files:**
- Modify: `app/Services/OperationalReportRecapService.php`
- Modify: `resources/views/pages/reports/preview.blade.php`
- Modify: `app/Services/ReportDocumentExporter.php`
- Modify: `tests/Feature/OperationalReportRecapTest.php`

**Interfaces:**
- Produces: row key `document_note: string` untuk preview/PDF/Excel.
- Preserves: `detail_note` sebagai Hasil dan `follow_up_label` sebagai Keterangan proyeksi Waka.

- [ ] **Step 1: Muat relasi Sumber tanpa query per baris**

Tambahkan `source` pada eager-load `caseQuery()`:

```php
'source',
'serviceField',
'status',
'followUpType',
```

- [ ] **Step 2: Tambahkan mapping Keterangan dokumen**

Di `recordRow()`, tambahkan key berikut tanpa mengubah `follow_up_label`:

```php
'document_note' => $isCase
    ? sprintf(
        "Sumber: %s\nTindak Lanjut: %s",
        $record->source?->label ?? '—',
        $record->followUpType?->label ?? '—',
    )
    : 'Selesai',
```

Jangan masukkan `document_note` ke allowlist `Arr::only()` untuk Waka.

- [ ] **Step 3: Pakai nilai dokumen pada preview dan Excel**

Ganti sel Keterangan dari `follow_up_label` ke `document_note` pada:

```blade
<td>{!! nl2br(e($row['document_note'])) !!}</td>
```

dan nilai kolom G Excel:

```php
$row['document_note'],
```

Gunakan style wrap yang sudah tersedia; jangan menambah kolom.

Selaraskan assertion existing agar preview dan Excel mengharapkan `Sumber:`
serta `Tindak Lanjut:` untuk Permasalahan dan `Selesai` untuk Konsultasi.

- [ ] **Step 4: Periksa diff statis dan commit**

```powershell
rg -n "document_note|follow_up_label" app/Services/OperationalReportRecapService.php app/Services/ReportDocumentExporter.php resources/views/pages/reports/preview.blade.php
git diff --check
git add app/Services/OperationalReportRecapService.php app/Services/ReportDocumentExporter.php resources/views/pages/reports/preview.blade.php tests/Feature/OperationalReportRecapTest.php
git commit -m "feat: lengkapi keterangan dokumen laporan"
```

### Task 2: Sederhanakan tabel dan kontrol detail

**Files:**
- Modify: `app/Services/OperationalReportRecapService.php`
- Modify: `resources/views/pages/reports/_desktop-table.blade.php`
- Modify: `resources/views/pages/reports/_mobile-cards.blade.php`
- Modify: `resources/js/app-dashboard.js`
- Modify: `resources/scss/app-dashboard.scss` only if existing selectors no longer fit.
- Modify: `tests/Feature/OperationalReportRecapTest.php`
- Modify: `tests/Feature/FrontendPreviewTest.php`

**Interfaces:**
- Consumes: `problem`, `handling`, dan `detail_note` dari row report.
- Produces: satu kontrol `[data-report-detail-toggle]` dengan target `aria-controls`.

- [ ] **Step 1: Ubah kolom laporan Guru BK/Koordinator**

Pada `reportData()`, gunakan:

```php
[
    'No',
    'Hari/Tanggal',
    'Nama & Kelas',
    'Layanan/Jenis Masalah',
    'Hasil',
    'Aksi',
]
```

Biarkan array kolom Waka tetap tujuh kolom yang sekarang.

- [ ] **Step 2: Ubah tabel desktop**

Hapus preview 80 karakter dan kedua tombol lama. Tampilkan Hasil pada row utama:

```blade
<td>{{ $row['detail_note'] }}</td>
```

Tambahkan satu tombol kaca pembesar:

```blade
<button
    class="btn btn-sm sibk-icon-button sibk-report-control"
    type="button"
    data-report-detail-toggle
    data-report-detail-name="{{ $row['name'] }}"
    aria-controls="{{ $detailId }}"
    aria-expanded="false"
    aria-label="Tampilkan detail layanan {{ $row['name'] }}"
    title="Tampilkan detail layanan"
>
```

Row target mulai setelah kolom No dan memuat dua blok saja:

```blade
<strong>Latar Belakang Masalah</strong>
<p>{{ $row['problem'] ?: '—' }}</p>
<strong>Penanganan</strong>
<p>{{ $row['handling'] ?: '—' }}</p>
```

Pertahankan form arsip sesuai `can_archive`.

- [ ] **Step 3: Samakan kartu mobile**

Tampilkan `Hasil` pada kartu utama dan letakkan Latar Belakang Masalah serta Penanganan di panel tersembunyi yang dikontrol tombol kaca pembesar yang sama. Jangan tampilkan Sumber atau Tindak Lanjut.

- [ ] **Step 4: Satukan handler JavaScript**

Hapus handler `[data-report-text-toggle]` dan `[data-report-result-toggle]`. Ganti dengan:

```js
document.querySelectorAll('[data-report-detail-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const detail = document.getElementById(button.getAttribute('aria-controls'));
        const expanded = button.getAttribute('aria-expanded') === 'true';

        detail.classList.toggle('d-none', expanded);
        button.setAttribute('aria-expanded', String(!expanded));
        button.title = expanded ? 'Tampilkan detail layanan' : 'Tutup detail layanan';
        button.setAttribute(
            'aria-label',
            `${button.title} ${button.dataset.reportDetailName}`,
        );
    });
});
```

- [ ] **Step 5: Rapikan selector yang tidak terpakai dan commit**

Gunakan class panel inset existing. Hapus selector SCSS hanya bila tidak lagi direferensikan.
Selaraskan assertion markup existing ke enam header Guru BK/Koordinator, satu
ikon detail, Hasil pada row utama, serta Latar Belakang Masalah/Penanganan pada
row tersembunyi. Jangan menambah suite baru dan jangan menjalankannya.

```powershell
rg -n "data-report-(text|result)-toggle|data-report-detail-toggle|sibk-report-result" resources
git diff --check
git add app/Services/OperationalReportRecapService.php resources/views/pages/reports/_desktop-table.blade.php resources/views/pages/reports/_mobile-cards.blade.php resources/js/app-dashboard.js resources/scss/app-dashboard.scss tests/Feature/OperationalReportRecapTest.php tests/Feature/FrontendPreviewTest.php
git commit -m "feat: sederhanakan detail tabel laporan"
```

### Task 3: Selaraskan kontrak dan handoff

**Files:**
- Modify: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Modify: `docs/api-contract.md`
- Modify: `docs/current-work.md`
- Modify: `docs/development-log.md`

**Interfaces:**
- Documents the exact row keys and role projection implemented by Tasks 1–2.

- [ ] **Step 1: Amendemen kontrak laporan**

Ubah REP-02 dan bagian laporan agar menetapkan Hasil sebagai kolom narasi utama, satu kaca pembesar untuk Latar Belakang Masalah/Penanganan, dan Keterangan dokumen Permasalahan sebagai Sumber + Tindak Lanjut terbaru. Tegaskan Waka tetap memakai proyeksi aman existing.

- [ ] **Step 2: Perbarui checkpoint**

Pindahkan koreksi laporan dari “belum diimplementasikan” ke hasil implementasi dan catat bahwa riwayat Tindak Lanjut tetap di luar scope.

- [ ] **Step 3: Pemeriksaan statis dan commit**

```powershell
rg -n "ikon chevron|Latar Belakang Masalah ringkas|Penanganan ringkas|Hasil|Tindak Lanjut" docs/requirements docs/api-contract.md docs/current-work.md
git diff --check
git add docs/requirements/PRD_Aplikasi_BK_v1.1.md docs/requirements/SRS_Aplikasi_BK_v1.1.md docs/api-contract.md docs/current-work.md docs/development-log.md
git commit -m "docs: selaraskan kontrak tampilan laporan"
```

Test dan gate repository sengaja tidak dijalankan sampai pengguna memberi perintah.
