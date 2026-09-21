# SIBK / Ruang BK

Aplikasi layanan Bimbingan dan Konseling untuk SMK Negeri 1 Surabaya.

## Mulai dan lanjutkan pekerjaan

1. Baca `docs/current-work.md` untuk checkpoint, branch, gate, dan task berikutnya.
2. Periksa `git status` dan `git log -1 --oneline`; pertahankan perubahan milik pengguna.
3. Baca hanya bagian plan aktif yang dibutuhkan. Selesaikan verifikasi task sebelum lanjut.
4. Perbarui handoff setelah task; catat ringkasan checkpoint selesai di `docs/development-log.md`.

`cobasidebar` adalah baseline pengembangan; `main` versi stabil untuk produksi.
Kerjakan di feature branch/worktree, gunakan PR ke `cobasidebar`, tanpa force push.
Gunakan Bahasa Indonesia sederhana untuk dokumentasi dan pesan commit.

Setelah PR berhasil di-merge, verifikasi status `MERGED` dan pastikan branch
checkpoint/fitur tidak berisi commit baru atau PR terbuka yang belum digabung.
Hapus branch sumber dari GitHub agar tidak menumpuk; jangan hapus `cobasidebar`
atau `main`. Jangan menghapus branch yang belum di-merge. Branch lokal/worktree
tidak otomatis ikut dihapus, terutama bila masih digunakan untuk checkpoint berikutnya.

## Source of truth

- `docs/requirements/SRS_Aplikasi_BK_v1.1.md`: spesifikasi perilaku aktif.
- `docs/requirements/PRD_Aplikasi_BK_v1.1.md`: kebutuhan produk aktif.
- `docs/requirements-index.md`: cari area/ID relevan sebelum membuka requirement.
- `docs/api-contract.md`: kontrak controller/service.
- `CONTEXT.md`: istilah ringkas; bukan pengganti PRD/SRS.
- Arsip v1.0 dan plan selesai: [repository privat terpisah](https://github.com/Aflahul/sibk-docs-archive).
  Dokumen arsip tidak diubah dan tidak menjadi baseline perilaku aktif.

Frontend memakai Penpot `22 — UI High-Fidelity Final` dan
`22.5 — Style Guide`. Page `21 — Wireframe Low-Fidelity Final` bukan referensi
implementasi. Jangan mendesain ulang UI yang disetujui; pakai komponen existing.

## Status dan scope disetujui

- Frontend tersedia; backend aktif dikembangkan.
- Plan integrasi Fase A Task 0–12 dan Portal Waka sudah selesai serta diarsipkan.
  Tidak wajib membaca ulang plan selesai pada setiap sesi.
- Enam checkpoint penyederhanaan disetujui 14 September 2026; spec dan plan aktif
  ditunjuk oleh `docs/current-work.md`.
- Data persiapan sementara, verifikasi Dapodik, dan aktivasi operasional tetap
  dipisahkan; fallback keterlambatan Dapodik 2–3 bulan berlaku pada baseline v1.1.
- Adapter production Dapodik/e-Tatib tetap di luar scope sampai kontrak resmi
  tersedia dan lolos `docs/integrations/provider-contract-admission.md`.
- Perluasan scope, arsitektur material, tindakan eksternal/destruktif di luar
  persetujuan yang sudah ada memerlukan persetujuan baru.

## Aturan implementasi

- Pertahankan arsitektur repository; jangan menambah fitur di luar PRD/SRS.
- Minimum PHP 8.3 atau lebih baru; kode mengikuti fitur PHP 8.3 dan
  `declare(strict_types=1);`.
- Thin Controller, Form Request, Service/Action layer, Query Scopes; pisahkan UI,
  akses data, logika bisnis, dan integrasi.
- Otorisasi/capability AUTH-01–AUTH-07 wajib di server/policy.
- Gunakan Bahasa Indonesia pada UI dan istilah `murid`, kecuali kutipan sumber resmi.
- Tulis PHP, Blade, HTML, dan JavaScript secara rapi dan terstruktur; pecah
  ekspresi panjang agar mudah dipindai.
- Jangan menulis tag Blade/HTML panjang dalam satu baris. Letakkan atribut pada
  baris terpisah dan pisahkan tag anak yang berbeda ke barisnya sendiri.
- Jangan menyimpan credential, payload mentah, `.env`, cache, atau build ke Git.
- Migration forward-only; jangan reset database shared/production.

## Verifikasi dan efisiensi context

Gate umum: `composer test`, `php vendor/bin/pint --test`,
`npm run check:frontend`, `npm run build`, `composer validate --strict`,
dan `git diff --check`. Tambahan hanya bila relevan dengan perubahan.

Test otorisasi umum tetap wajib; screenshot/workbook penelitian tidak diwajibkan.
Jangan buka PRD/SRS penuh pada setiap task; gunakan indeks lalu section/ID relevan.
Pekerjaan visual murni cukup Hi-Fi, Style Guide, dan existing code.
Jangan menggandakan isi requirement atau log panjang di rules/workflows.
