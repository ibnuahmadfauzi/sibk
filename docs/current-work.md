# Pekerjaan Aktif Ruang BK

## Status

- Pekerjaan aktif: revisi halaman Laporan Guru BK/Koordinator; source of truth
  dan implementation plan siap ditinjau.
- Branch aktif: `cobasidebar`; commit terakhir saat perencanaan `1d6e848`.
- Checkpoint 7A, 7B, dan 7C telah terintegrasi melalui PR #25, #26, dan #27.
- Branch sumber remote/lokal dan worktree Checkpoint 7C telah dibersihkan.
- `main` tidak disentuh.

## Rencana revisi laporan 21 September 2026

- Plan: `docs/superpowers/plans/2026-09-21-revisi-laporan-catatan-layanan.md`.
- Status: PRD/SRS, indeks, API contract, spec, dan plan telah diperbarui;
  implementasi belum dimulai.
- Asumsi plan: halaman tiga tab Guru BK/Koordinator diganti daftar per catatan
  kasus/konsultasi; Portal Waka tidak berubah.
- Keputusan UI: satu tombol `Cetak / Unduh Rekap` membuka preview terpadu dengan
  pilihan Excel, Word, dan Cetak/Simpan PDF. Modal serta preview satu layanan
  memakai Permasalahan, Penanganan, lalu Catatan Penyelesaian/Hasil.
- Amandemen 22 September: rekap memakai A4 landscape dan seluruh hasil filter;
  dokumen individual memakai A4 portrait. Rekap ditandatangani Koordinator BK
  dan Waka, sedangkan dokumen individual memakai Guru BK penanggung jawab dan
  Waka. NIP tidak ditampilkan karena belum tersedia pada schema.
- Partial kop dan tanda tangan wajib reusable. Kondisi akun Koordinator/Waka
  kosong atau ganda tidak memilih akun pertama dan menampilkan status belum
  tersedia.
- Blocker keputusan: penambahan `phpoffice/phpspreadsheet` diperlukan untuk
  `.xlsx` native. Word awal memakai Blade `.doc` kompatibel tanpa PHPWord.
- Tidak ada test atau gate yang dijalankan sesuai instruksi pengguna.

## Hasil Checkpoint 7C

- Migration forward-only menghapus tabel `consultation_private_notes`,
  `follow_ups`, dan `case_coordinations` setelah consumer runtime bersih.
- Kolom retired pada kasus (`waka_summary`, `final_result`, `continued_plan`)
  dan sembilan kolom retired konsultasi telah dihapus.
- Consumer sementara pada model, service, seeder, dan fixture test diselaraskan
  ke skema final; klasifikasi tindak lanjut singular tetap aktif pada kasus.
- Jalur SQLite eksplisit mempertahankan data aktif dan foreign key karena
  `references` merupakan nama reserved; jalur database lain memakai Schema
  Builder.
- Tidak ada dependency, route, adapter production, atau perubahan UI baru.

## Bukti verifikasi

- TDD skema final gagal sebelum migration lalu lulus setelah implementasi.
- Focused migration gate: 42 test / 338 assertion.
- Focused security/privacy gate: 88 test / 824 assertion.
- Full gate kandidat 7C: 454 test / 3.540 assertion.
- SQLite disposable lulus `migrate:fresh`, rollback, dan upgrade dengan 15 data
  konsultasi tetap tersedia setelah migration.
- MySQL 8.4 disposable lulus fresh, rollback, dan upgrade; 15 kasus serta 15
  konsultasi tetap tersedia dan indeks foreign key/rollback terverifikasi.
- Review independen menemukan dua masalah indeks Important; keduanya diperbaiki
  dengan regression test. Tidak ada temuan Critical atau Minor.
- `php vendor/bin/pint --test`: lulus.
- `npm run check:frontend`: lulus.
- `npm run build`: lulus.
- `composer validate --strict`: lulus.
- `git diff --check`: lulus.
- Verifikasi memakai `APP_KEY` testing dan `CACHE_STORE=array` hanya pada
  environment proses; tidak ada `.env` atau credential dibuat.

## Hasil scan consumer

Scan ketat tidak menemukan consumer skema retired pada runtime. Match tersisa:

- assertion bahwa tabel/kolom retired sudah tidak ada;
- probe migration historis timestamp koordinasi;
- istilah `followUps` pada rekap aktif berbasis status `BkCase`.

Match tersebut bukan consumer tabel retired. Placeholder yang ditemukan scan
adalah atribut input HTML dan Laporan Akhir Waka yang memang disetujui.

## Batas wajib

- Migration 7C hanya boleh dijalankan lewat prosedur deployment; database
  shared/production tidak boleh di-reset.
- Jangan menambah dependency.
- Semua PR checkpoint menargetkan `cobasidebar`; `main` hanya untuk PR rilis
  terpisah setelah persetujuan pengguna.
- Driver production Dapodik/e-Tatib tetap `unavailable` sampai kontrak resmi
  lolos admission gate.
- Jangan menghapus view detail Waka aktif; view tersebut memakai proyeksi
  allowlist dan audit pembukaan.

## Langkah berikutnya

1. Tinjau hasil amandemen kedua dan plan revisi laporan.
2. Putuskan izin penambahan PhpSpreadsheet untuk `.xlsx` native.
3. Setelah disetujui, buat feature branch/worktree dari `cobasidebar` dan mulai
   Task 2; jangan menyentuh `main`.

## Blocker

- Excel `.xlsx` native memerlukan pengecualian aturan dependency aktif.
- Risiko residual: Waka membaca narasi terstruktur sesuai keputusan BK; draft
  `localStorage` hanya tersedia pada perangkat/browser yang sama.

## Acuan

- Laporan progress:
  `docs/laporan-progress-revisi-sibk-3-2.md`
- Plan selesai:
  `docs/superpowers/plans/2026-09-17-revisi-sibk-3-2-guru-bk.md`
- Spec aktif:
  `docs/superpowers/specs/2026-09-17-revisi-sibk-3-2-guru-bk-design.md`
- Matriks otorisasi: `docs/testing/authorization-matrix.md`
- Kontrak provider: `docs/integrations/provider-contract-admission.md`
- Requirement index: `docs/requirements-index.md`
