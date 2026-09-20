# Pekerjaan Aktif Ruang BK

## Status

- Pekerjaan aktif: tidak ada; Revisi SIBK 3.2 selesai sampai Checkpoint 7C.
- Branch aktif: `cobasidebar` pada merge commit PR #27 `6e16498`.
- Checkpoint 7A, 7B, dan 7C telah terintegrasi melalui PR #25, #26, dan #27.
- Branch sumber remote/lokal dan worktree Checkpoint 7C telah dibersihkan.
- `main` tidak disentuh.

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

1. Pause pada `cobasidebar`; tidak ada task implementasi aktif.
2. Jangan membuat PR rilis ke `main` tanpa persetujuan pengguna.
3. Jika rilis disetujui, gunakan PR rilis terpisah dan prosedur deployment
   dengan backup serta migration forward-only.

## Blocker

- Tidak ada blocker implementasi atau gate; rilis sengaja belum dimulai.
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
