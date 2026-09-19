# Pekerjaan Aktif Ruang BK

## Status

- Pekerjaan aktif: Revisi SIBK 3.2 untuk Layanan Guru BK.
- Branch Checkpoint 7B: `revisi-sibk-3-2-7b-runtime`.
- Base: `origin/cobasidebar` pada merge commit Checkpoint 7A `85b2ce1`.
- Commit runtime Checkpoint 7B: `4a5fcd2`.
- PR #26 mengintegrasikan Checkpoint 7B ke `cobasidebar`; status akhir wajib
  diverifikasi `MERGED` sebelum pekerjaan berikutnya.
- Branch sumber remote dihapus setelah status merge terverifikasi.
- Checkpoint selesai: 1–7B.
- Checkpoint berikutnya: 7C — Cleanup Skema dan Gate Akhir, belum dimulai.
- `main` tidak disentuh.

## Hasil Checkpoint 7B

- Model, service, controller, request, route, view, relasi, dan policy ability
  untuk event tindak lanjut lama, koordinasi kasus, private note, serta resolve
  terpisah telah dihapus.
- Route mutasi lama kini 404. Penyelesaian tetap melalui `cases.update`.
- Klasifikasi tindak lanjut aktif tetap memakai endpoint singular
  `cases.follow-up.update` dan field `follow_up_type_id` pada kasus.
- Consumer laporan lama yang membaca tabel `follow_ups`, termasuk branch
  export mati, telah dihapus.
- View detail Waka berbasis allowlist dipertahankan karena masih menjadi runtime
  aktif yang aman.
- Tidak ada migration, drop tabel/kolom, perubahan dependency, atau adapter
  production.

## Bukti verifikasi

- Baseline sebelum perubahan: 454 test / 3.529 assertion.
- TDD endpoint retired: test gagal 403 sebelum route dihapus, lalu lulus 404.
- Focused gate akhir: 135 test / 1.096 assertion.
- Full gate pada commit `4a5fcd2`: 453 test / 3.524 assertion.
- `php vendor/bin/pint --test`: lulus.
- `npm run check:frontend`: lulus.
- `npm run build`: lulus.
- `composer validate --strict`: lulus.
- `git diff --check`: lulus.
- Verifikasi memakai `APP_KEY` testing dan `CACHE_STORE=array` hanya pada
  environment proses; tidak ada `.env` atau credential dibuat.

## Hasil scan consumer

Scan ketat berikut tidak menemukan consumer retired pada runtime:

- class/model `FollowUp`, `CaseCoordination`, dan
  `ConsultationPrivateNote`;
- relasi `followUps`, `coordinations`, dan `privateNote`;
- route plural `cases.follow-ups.*`, `cases.coordinations.*`, dan
  `cases.resolve*`;
- field event `planned_date`, `execution_date`, `coordination_need`, dan
  `coordinated_at` pada `app`, `routes`, `resources`, serta
  `database/seeders`.

Command scan plan tetap menghasilkan match `FollowUp` untuk klasifikasi aktif:
endpoint singular, request/controller/service dropdown, query laporan berbasis
`BkCase`, dashboard, dan JavaScript. Match ini bukan dependency pada tabel
`follow_ups`.

## Batas wajib

- Jangan membuat migration atau drop skema sebelum PR 7B terverifikasi
  `MERGED`.
- Migration 7C harus forward-only dan hanya diuji pada database disposable;
  database shared/production tidak boleh di-reset.
- Jangan menambah dependency.
- Semua PR checkpoint menargetkan `cobasidebar`; `main` hanya untuk PR rilis
  terpisah setelah persetujuan pengguna.
- Driver production Dapodik/e-Tatib tetap `unavailable` sampai kontrak resmi
  lolos admission gate.
- Jangan menghapus view detail Waka aktif; view tersebut memakai proyeksi
  allowlist dan audit pembukaan.

## Langkah berikutnya

1. Pause setelah PR #26 terverifikasi `MERGED` dan branch sumber remote
   terhapus.
2. Saat 7C dilanjutkan, sinkronkan `cobasidebar` lalu buat feature branch baru.
3. Ulangi scan dependency sebelum migration forward-only pada database
   disposable.

## Blocker

- Tidak ada blocker implementasi atau gate.
- Risiko residual: Waka membaca narasi terstruktur sesuai keputusan BK; draft
  `localStorage` hanya tersedia pada perangkat/browser yang sama; skema
  retired baru dihapus pada Checkpoint 7C.

## Acuan

- Laporan progress:
  `docs/laporan-progress-revisi-sibk-3-2.md`
- Plan aktif:
  `docs/superpowers/plans/2026-09-17-revisi-sibk-3-2-guru-bk.md`
- Spec aktif:
  `docs/superpowers/specs/2026-09-17-revisi-sibk-3-2-guru-bk-design.md`
- Matriks otorisasi: `docs/testing/authorization-matrix.md`
- Kontrak provider: `docs/integrations/provider-contract-admission.md`
- Requirement index: `docs/requirements-index.md`
