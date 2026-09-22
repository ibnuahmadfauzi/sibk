# Revisi Laporan Catatan Layanan BK Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use
> `superpowers:subagent-driven-development` or `superpowers:executing-plans`
> after the user approves this plan. Do not implement, create tests, or run
> verification gates before that approval and a separate testing instruction.

**Goal:** Mengganti halaman Laporan Guru BK/Koordinator menjadi daftar catatan
kasus dan konsultasi yang dapat difilter, dibuka inline, serta direkap melalui
preview, cetak/PDF, Excel, atau Word.

**Architecture:** Gunakan route, policy, soft delete, Bootstrap,
dan pagination Laravel yang sudah ada. Satu query dasar terscope menghasilkan
pagination untuk UI dan seluruh dataset untuk dokumen. Preview rekap memakai
partial kop dan tanda tangan bersama; jangan membuat template builder atau
framework ekspor generik.

**Tech Stack:** PHP 8.3+, Laravel 13, Eloquent/Query Builder, Form Request,
Blade, Bootstrap 5.3.8, SCSS existing, JavaScript existing, Laravel Pagination.

**Spec:**
`docs/superpowers/specs/2026-09-14-penyederhanaan-laporan-guru-koordinator-design.md`

## Status Kelayakan

**Layak bersyarat.** UI, query, detail inline, archive, pagination, preview,
print, dan Word `.doc` dapat memakai fondasi existing tanpa migration.

- Excel `.xlsx` native memerlukan persetujuan penambahan
  `phpoffice/phpspreadsheet`.
- Identitas Guru BK, Koordinator BK, dan Waka Kesiswaan dapat diambil dari akun
  dan penugasan existing.
- Role Kepala Sekolah dan field NIP belum tersedia. Jangan hardcode atau
  menjanjikan nama/NIP Kepala Sekolah.
- Jika akun Koordinator atau Waka aktif tidak dapat ditentukan secara tunggal,
  tampilkan `Penandatangan belum tersedia`; jangan memilih akun pertama.

## Asumsi Produk yang Dikunci

- Halaman `/reports` hanya memuat catatan Kasus dan Konsultasi.
- Filter hanya `academic_year_id`, `classroom_id`, dan
  `service_type=all|case|consultation`.
- Tahun ajaran default adalah tahun ajaran aktif.
- Opsi kelas hanya berasal dari tahun ajaran terpilih. Backend menolak pasangan
  tahun ajaran dan kelas yang tidak cocok atau berada di luar scope actor.
- UI mengurutkan tanggal layanan `DESC`, lalu tipe dan ID sebagai tie-breaker.
- Preview dan unduhan mengurutkan tanggal `ASC`, lalu tipe dan ID.
- Pagination 10/25/50/100, default 10, hanya memengaruhi halaman UI. Preview,
  Excel, dan Word selalu mengambil seluruh dataset hasil filter.
- Hapus memakai archive/soft delete existing, bukan hard delete.
- Header tabel: No; Hari/Tanggal; Nama & Kelas; Layanan/Jenis Masalah; Latar
  Belakang Masalah; Penanganan; Aksi.
- Latar Belakang Masalah memakai `cases.initial_info` atau `consultations.problem`.
- Penanganan memakai `cases.initial_action` atau `consultations.handling`.
- Baris Hasil Layanan memakai `resolution_summary` untuk kasus atau `result`
  untuk konsultasi.
- `internal_note` kasus tidak pernah masuk daftar, preview, atau dokumen.
- Teks tabel dibatasi 80 karakter; kontrol detail membuka nilai lengkap dan ikon
  chevron membuka baris Hasil Layanan dengan latar berbeda.
- Pojok atas hanya berisi tombol `Cetak / Unduh Rekap`. Tombol membuka preview
  web; tidak ada download atau dialog print langsung dari halaman daftar.
- Preview rekap memakai A4 landscape dan seluruh data hasil filter.
- Rekap ditandatangani Koordinator BK dan Waka Kesiswaan.
- NIP tidak ditampilkan sampai tersedia sumber data resmi.
- Tanda tangan hanya berada di akhir dokumen dan tidak boleh terpotong halaman.
- Excel tidak memuat blok tanda tangan. Preview cetak dan Word memuatnya.
- Template resmi masih diproses; gunakan layout netral dan partial reusable.

## Batas Wajib

- AUTH-01–AUTH-07 dan scope `accessibleTo()` berlaku pada daftar, detail,
  preview, unduhan, URL langsung, dan archive.
- Guru BK hanya melihat data dalam scope profesional/kasus khusus; Koordinator
  melihat gabungan yang diizinkan; Admin IT dan Waka ditolak dari pusat laporan.
- Jangan tampilkan kode kasus, catatan internal, payload provider, NISN, atau
  field rahasia lain.
- Gunakan nama dan kelas yang berlaku pada tanggal layanan, termasuk identitas
  sementara yang sah.
- Tombol ikon wajib semantik, ber-`aria-label`, `title`, focus ring terlihat,
  dan target sentuh minimal 44 × 44 px.
- Klik baris tidak membuka modal. Kontrol detail, chevron, dan arsip dapat dicapai
  keyboard tanpa bergantung pada interaksi hover.
- Pertahankan kartu mobile existing agar tidak ada overflow horizontal.
- Kode dan markup mengikuti aturan multi-line di `AGENTS.md`.
- Jangan membuat migration atau tabel baru.
- Jangan membuat test atau menjalankan test, formatter, build, maupun gate
  sebelum pengguna memberi perintah terpisah.

---

### Task 1: Amandemen Requirement dan Kontrak

**Status:** Selesai 21 September 2026; diamendemen 22 September 2026.

**Files:**

- Modify: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Modify: `docs/requirements-index.md`
- Modify: `docs/api-contract.md`
- Modify: `docs/superpowers/specs/2026-09-14-penyederhanaan-laporan-guru-koordinator-design.md`

**Hasil:**

- Tiga tab diganti satu daftar per catatan kasus/konsultasi.
- Kontrak mengunci tiga filter, tujuh kolom, pagination UI, seluruh dataset
  dokumen, dua arah urutan, preview web, ekspor Office, dan soft delete.
- Terminologi tabel memakai Penanganan; field ketiga detail tetap Catatan
  Penyelesaian untuk kasus dan Hasil untuk konsultasi.
- Kontrak memakai preview rekap landscape tanpa akses cetak individual dari UI.
- Kontrak tanda tangan rekap memakai Koordinator dan Waka tanpa NIP.
- Route preview tidak menerima format; format hanya berada pada route ekspor.

---

### Task 2: Ganti Builder Rekap Menjadi Dataset Catatan

**Files:**

- Modify: `app/Http/Requests/OperationalReportRequest.php`
- Modify: `app/Contracts/OperationalReportRecap.php`
- Modify: `app/Services/OperationalReportRecapService.php`
- Modify: `app/Policies/ReportPolicy.php`

**Hasil:**

- Request menerima tiga filter, `per_page` allowlist, parameter halaman, dan
  format Office hanya pada endpoint ekspor.
- Request memvalidasi bahwa kelas berasal dari tahun ajaran terpilih dan scope
  actor, bukan hanya mempercayai dropdown.
- Service membuat query kasus dan konsultasi terscope dengan bentuk sama lalu
  `UNION ALL` dan tie-breaker stabil.
- Satu builder dasar menyediakan `paginateForUi()` untuk urutan terbaru dan
  `allForDocument()` untuk seluruh data urutan paling awal atau nama ekuivalen
  yang tetap memisahkan terminal pagination dari dokumen.
- Row berisi nomor tampilan, identitas, kelas historis, layanan, tanggal,
  permasalahan, penanganan, URL detail, URL preview, URL archive, dan capability
  archive.
- Query string filter dipertahankan saat berpindah halaman.

---

### Task 3: Susun Ulang Halaman Laporan

**Files:**

- Modify: `resources/views/pages/reports/index.blade.php`
- Modify: `resources/views/pages/reports/_filters.blade.php`
- Modify: `resources/views/pages/reports/_desktop-table.blade.php`
- Modify: `resources/views/pages/reports/_mobile-cards.blade.php`
- Modify: `resources/scss/app-dashboard.scss`

**Hasil:**

- Hapus tab, pencarian nama, rentang tanggal, filter Guru BK, statistik, CSV,
  dan tombol cetak langsung dari halaman laporan.
- Tampilkan filter Tahun Ajaran, Kelas, Jenis Layanan BK, dan jumlah data.
- Perubahan tahun ajaran memperbarui opsi kelas dan kembali ke halaman pertama.
- Tabel memakai header: No; Hari/Tanggal; Nama & Kelas; Layanan/Jenis Masalah;
  Latar Belakang Masalah; Penanganan; Aksi.
- Pojok atas memakai satu tombol `Cetak / Unduh Rekap` menuju preview rekap.
- Klik baris tidak membuka modal. Kontrol detail membuka narasi lengkap dan ikon
  chevron membuka baris Hasil Layanan dengan latar berbeda.
- Aksi cetak individual dihilangkan; hapus hanya tampil jika policy mengizinkan.
- Empty state, kartu mobile, fokus keyboard, label aksesibel, dan target sentuh
  dipertahankan.

---

### Task 4: Tambahkan Preview Rekap

**Files:**

- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/ReportController.php`
- Replace: `resources/views/pages/reports/preview.blade.php`
- Create: `resources/views/pages/reports/record-preview.blade.php`
- Modify: `resources/views/pages/cases/_detail-modal.blade.php`
- Modify: `resources/views/pages/consultations/_detail-modal.blade.php`

**Hasil:**

- `GET /reports/preview` menampilkan seluruh hasil filter dalam A4 landscape,
  tanpa download dan tanpa membuka dialog print otomatis.
- Tabel rekap polos menampilkan Hari/Tanggal, Nama/Kelas, jenis catatan dan
  bidang layanan, Permasalahan, Penanganan beserta hasil, serta Tindak
  Lanjut/Status. Jam tidak ditampilkan karena schema aktif tidak menyimpannya.
- Action bar rekap: Kembali, Download Excel, Download Word, Cetak/Simpan PDF.
- Halaman laporan dan modal layanan tidak menampilkan akses cetak individual.
- Rekap memakai urutan tanggal paling awal; nomor mengikuti urutan dokumen.
- `window.print()` hanya dipanggil dari tombol pada halaman preview.

---

### Task 5: Tambahkan Layout Dokumen dan Penandatangan

**Files:**

- Create: `app/Services/ReportSignatoryResolver.php`
- Create: `resources/views/pages/reports/print/_letterhead.blade.php`
- Create: `resources/views/pages/reports/print/_signature-block.blade.php`
- Modify: `resources/views/pages/reports/preview.blade.php`
- Modify: `resources/views/pages/reports/record-preview.blade.php`
- Modify: `resources/scss/app-dashboard.scss`

**Hasil:**

- Partial kop dipakai oleh preview rekap, kasus, konsultasi, dan Word.
- Partial tanda tangan menerima dua slot peran/nama dan dipakai ulang oleh
  preview serta Word.
- Resolver memilih Koordinator dan Waka hanya jika tepat satu akun aktif dengan
  role terkait tersedia; kondisi kosong/ganda menghasilkan status
  `Penandatangan belum tersedia`.
- Rekap: Koordinator BK dan Waka Kesiswaan.
- Kasus: owner terakhir dari assignment `TYPE_OWNER` dan Waka Kesiswaan.
- Konsultasi: relasi `counselor` dan Waka Kesiswaan.
- Pengguna login tidak digunakan sebagai fallback penandatangan.
- Nama ditampilkan tanpa NIP.
- Blok tanda tangan berada setelah isi terakhir dan memakai
  `break-inside: avoid`/`page-break-inside: avoid`.

---

### Task 6: Buat Unduhan Excel dan Word

**Files:**

- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `app/Http/Controllers/ReportController.php`
- Create: `app/Services/ReportDocumentExporter.php`
- Create: `resources/views/pages/reports/exports/word.blade.php`

**Hasil:**

- Tambahkan `phpoffice/phpspreadsheet` hanya setelah persetujuan dependency;
  jangan menambahkan PHPWord pada tahap ini.
- `GET /reports/export?format=xlsx|doc` memakai seluruh dataset, filter, scope,
  dan urutan yang sama seperti preview.
- XLSX dan DOC memuat narasi lengkap yang diizinkan, bukan potongan UI.
- Excel tidak memuat tanda tangan; Word memuat partial kop dan tanda tangan.
- Word berupa HTML Blade ber-MIME `application/msword` dan ekstensi `.doc`.
- Data pengguna selalu memakai escaping default Blade.
- Nama file: `laporan-layanan-bk-YYYYMMDD-HHmmss.xlsx|doc`.
- Temporary file selalu dibersihkan setelah response dikirim.
- Nilai spreadsheet yang diawali `=`, `+`, `-`, `@`, tab, atau carriage return
  dinetralkan untuk mencegah formula injection.

---

### Task 7: Pensiunkan Jalur Lama dan Perbarui Handoff

**Files:**

- Modify/Delete setelah scan consumer: legacy report query/view yang tidak lagi
  dipakai di `app/Services`, `resources/views/pages/reports`, dan route report.
- Modify: `docs/current-work.md`
- Modify: `docs/development-log.md`

**Hasil:**

- Hapus tiga tab, CSV, dan preview legacy hanya jika `rg` membuktikan tidak ada
  consumer runtime.
- Catat dependency, route, mapping field, penandatangan, orientasi halaman,
  urutan data, serta status template resmi.
- Implementasi berhenti dengan status `MENUNGGU VERIFIKASI`.
- Test, formatter, build, dan gate tidak dibuat atau dijalankan sampai pengguna
  memberi perintah terpisah.

## Skenario Verifikasi yang Dicatat, Belum Dijalankan

- Pasangan kelas dan tahun ajaran tidak cocok harus ditolak server.
- Pagination UI tidak boleh mengurangi isi preview atau file unduhan.
- Guru BK tidak boleh melihat record di luar scope atau membuka URL langsung.
- Catatan internal, kode kasus, dan NISN tidak boleh muncul pada keluaran.
- Penandatangan kosong/ganda tidak boleh dipilih secara diam-diam.
- Rekap harus landscape dan individual portrait saat dicetak.

Bagian ini bukan task pembuatan test. Implementasi maupun eksekusi test menunggu
perintah pengguna.

## Urutan Implementasi

1. Setujui penambahan PhpSpreadsheet untuk Excel `.xlsx`.
2. Jalankan Task 2–4 sebagai checkpoint UI dan preview.
3. Jalankan Task 5 untuk layout serta tanda tangan.
4. Jalankan Task 6 untuk unduhan Office.
5. Jalankan Task 7 dan berhenti menunggu perintah verifikasi.

## Estimasi

- Task 2–4: 4–6 jam.
- Task 5: 1–2 jam.
- Task 6: 1–2 jam setelah dependency tersedia.
- Task 7: 30–60 menit.
- Total implementasi: sekitar 1–1,5 hari kerja, tidak termasuk testing/UAT dan
  finalisasi visual template resmi.
