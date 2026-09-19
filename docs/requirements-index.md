# Ruang BK — Requirements Index

Tujuan file ini adalah membantu agent menentukan apakah PRD/SRS perlu dibuka. Jangan membaca dokumen requirement penuh pada setiap task.

## Baseline Final Saat Ini

- Product Requirement Document: `docs/requirements/PRD_Aplikasi_BK_v1.1.md`
- Software Requirements Specification: `docs/requirements/SRS_Aplikasi_BK_v1.1.md`
- Status keduanya: baseline Markdown aktif untuk pengembangan MVP.
- Tanggal baseline: 23 Agustus 2026; amandemen sampai 15 September 2026 tetap berlaku. Revisi SIBK 3.2 disetujui 17 September 2026 dan menggantikan ketentuan yang bertentangan tentang lifecycle kasus, konsultasi mandiri, akses Waka, audit, autosave, sorting, dan concurrency tanpa mengubah versi atau nama file v1.1.

## Arsip Baseline Sebelumnya

- [PRD/SRS v1.0 DOCX dan Markdown](https://github.com/Aflahul/sibk-docs-archive/tree/main/requirements/v1.0) tersedia di repository arsip privat, bukan folder aplikasi aktif.
- Tanggal baseline arsip: 15 Agustus 2026.
- DOCX v1.0 tetap menjadi baseline manusia sebelumnya dan referensi struktur/visual; seluruh arsip dipertahankan byte-for-byte dan tidak menjadi source of truth perilaku aktif.

## Aturan Penggunaan Context

### Jangan buka PRD/SRS bila task hanya:
- menyesuaikan CSS atau spacing;
- membuat komponen visual yang sudah jelas dari Penpot;
- memperbaiki layout;
- menjalankan lint/build;
- memperbaiki bug presentasi yang tidak mengubah perilaku produk.

Untuk task tersebut gunakan:
1. Penpot `22 — UI High-Fidelity Final`;
2. Penpot `22.5 — Style Guide`;
3. existing code/components.

### Buka PRD bila perlu memastikan:
- scope P0/P0 bertahap/P1;
- tujuan produk;
- prioritas;
- batas MVP;
- peran dan tata kelola secara produk;
- area yang secara eksplisit berada di luar scope.

### Buka SRS bila perlu memastikan:
- aturan akses/authorization;
- perilaku sistem;
- acceptance criteria;
- field minimum;
- status dan perubahan;
- privacy/audit;
- integrasi;
- kebutuhan nonfungsional;
- perilaku saat error/sinkronisasi/rekonsiliasi.

### Saat membuka requirement
- prioritaskan file `.md` untuk pencarian dan selective reading;
- buka `.docx` hanya bila perlu memeriksa dokumen baseline manusia atau formatting;
- cari bagian/ID yang relevan saja;
- jangan merangkum ulang seluruh dokumen;
- jangan menyalin requirement ke file rule lain;
- simpan keputusan implementasi hanya bila benar-benar diperlukan.

## Peta Cepat PRD v1.1

- Bagian awal: ringkasan produk, masalah, dasar kebutuhan, visi, tujuan, dan indikator keberhasilan.
- Bagian tengah: pengguna/tata kelola, cakupan P0/P0 bertahap/P1, di luar scope, aturan produk utama, data persiapan sementara, aktivasi operasional, rollover, status pelayanan, kode kasus internal, dan pemantauan Waka.
- Bagian akhir: arsitektur informasi, laporan P0, Portal Waka berbasis tujuan, risiko integrasi, dependensi kontrak, dan riwayat versi.

## Peta Cepat SRS v1.1

- Bagian awal: batas sistem, hak akses/tata kelola, dan `AUTH-*`.
- Kebutuhan fungsional: akun, data master, identitas sementara, penugasan, kasus/tindak lanjut/koordinasi, integrasi, konsultasi, profil, prestasi, dashboard, laporan, notifikasi, audit, dan koreksi.
- Bagian data/privasi: field minimum, entitas konseptual, status/perubahan, privasi, keamanan, dan audit.
- Bagian integrasi/keamanan: `INT-05`–`INT-11`, `MD-05`–`MD-12`, `NFR-09`–`NFR-13`, identitas sumber, data persiapan sementara, aktivasi operasional, pratinjau pencocokan, secret-safe workflow, endpoint/DNS binding, snapshot evidence/validator, limits, fencing/deadline, dan driver admission gate.
- Bagian akhir: integrasi e-Tatib/Dapodik, ketertelusuran, dependensi yang belum dikunci, dan riwayat versi.

## Requirement ID penting menurut area

- Access/authorization: `AUTH-*`, `GOV-01`
- Account/master: `ACC-*`, `MD-*`; keterlambatan Dapodik, rollover, dan cakupan murid aktif: `MD-05`–`MD-14`, `NFR-13`; proses keluar murid: `MD-15`–`MD-18`
- Assignment: `ASN-*`
- Reference values: `REF-01`
- Case/follow-up/coordination: `CASE-*`; pemilik edit/arsip, klasifikasi tindak lanjut terkini, kode internal, dan ringkasan aman Waka: `CASE-13`–`CASE-16`
- Integration Dapodik/e-Tatib: `INT-*`, `DEP-01`, `DEP-02`
- Consultation: `CONS-*`; konsultasi mandiri tanpa nomor registrasi atau status terpisah: `CONS-01`–`CONS-05`
- Student profile/history: `STU-*`
- Achievement: `ACH-*`
- Dashboard: `DASH-*`
- Reports: `REP-01`–`REP-04` untuk tiga tab Pelanggaran & Poin, Layanan BK, dan Prestasi; `DASH-03`, `REP-05` untuk Dashboard, Murid dengan Kasus, Monitoring Penanganan, Rekap Periode, dan placeholder Laporan Akhir Waka.
- Audit append-only tanpa halaman pembaca MVP: `AUD-01`
- Nonfunctional: `NFR-*`

## Revisi SIBK 3.2

- Akses Waka: `AUTH-04`, `AUTH-05`.
- Kasus: `CASE-04`, `CASE-05`, `CASE-12`, `CASE-16` hingga `CASE-18`.
- Konsultasi mandiri: `CONS-01` hingga `CONS-05`.
- Autosave lokal: `DASH-04`; audit perubahan-delta: `AUD-01`.
- Kontrak endpoint, sorting allowlist, dan `expected_updated_at`: modul Kasus,
  Klasifikasi Tindak Lanjut, dan Konsultasi pada `docs/api-contract.md`.

## Source of Truth Priority

Behavior:
1. SRS v1.1
2. PRD v1.1

Visual:
1. Penpot `22 — UI High-Fidelity Final`
2. Penpot `22.5 — Style Guide`

Halaman pengaturan koneksi PG-501 diimplementasikan langsung menggunakan komponen dan style aplikasi yang sudah ada; tidak memerlukan artefak Penpot baru.

Istilah lintas dokumen tersedia secara ringkas pada `CONTEXT.md`. Keputusan pemisahan verifikasi sumber dan aktivasi operasional dicatat pada `docs/adr/0001-separate-data-verification-from-operational-activation.md`.

Jika visual dan behavior tampak bertentangan, jangan menebak. Periksa requirement terkait dan laporkan konflik.
