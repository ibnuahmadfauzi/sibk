# Ruang BK — Requirements Index

Tujuan file ini adalah membantu agent menentukan apakah PRD/SRS perlu dibuka. Jangan membaca dokumen requirement penuh pada setiap task.

## Baseline Final

- Product Requirement Document (baseline manusia): `docs/requirements/PRD_Aplikasi_BK_v1.0.docx`
- Product Requirement Document (mirror AI): `docs/requirements/PRD_Aplikasi_BK_v1.0.md`
- Software Requirements Specification (baseline manusia): `docs/requirements/SRS_Aplikasi_BK_v1.0.docx`
- Software Requirements Specification (mirror AI): `docs/requirements/SRS_Aplikasi_BK_v1.0.md`
- Status keduanya: baseline final untuk pengembangan MVP.
- Tanggal baseline: 15 Agustus 2026.

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

## Peta Cepat PRD v1.0

- Hal. 1–2: ringkasan produk, masalah, dasar kebutuhan, visi, tujuan.
- Hal. 3: indikator keberhasilan, pengguna dan tata kelola.
- Hal. 4–5: cakupan P0/P0 bertahap/P1, di luar scope, aturan produk utama.
- Hal. 5–6: arsitektur informasi, laporan P0, risiko.
- Hal. 7: dependensi yang belum dikunci dan riwayat versi.

## Peta Cepat SRS v1.0

- Hal. 1–2: batas sistem, hak akses/tata kelola, AUTH.
- Hal. 3: akun, data master, identitas sementara, penugasan.
- Hal. 3–4: CASE dan aturan kasus/tindak lanjut/koordinasi.
- Hal. 4–5: integrasi, konsultasi, profil murid, prestasi, dashboard, laporan, notifikasi, audit, koreksi.
- Hal. 6–7: kebutuhan data, entitas konseptual, status/perubahan, privasi.
- Hal. 8–9: keamanan, NFR, integrasi e-Tatib/Dapodik, ketertelusuran.
- Hal. 10: dependensi yang belum dikunci dan riwayat versi.

## Requirement ID penting menurut area

- Access/authorization: `AUTH-*`, `GOV-01`
- Account/master: `ACC-*`, `MD-*`
- Assignment: `ASN-*`
- Reference values: `REF-01`
- Case/follow-up/coordination: `CASE-*`
- Integration e-Tatib: `INT-*`
- Consultation: `CONS-*`
- Student profile/history: `STU-*`
- Achievement: `ACH-*`
- Dashboard: `DASH-*`
- Reports: `REP-*`
- Notification: `NOT-01`
- Audit: `AUD-01`
- Correction: `COR-*`
- Nonfunctional: `NFR-*`

## Source of Truth Priority

Behavior:
1. SRS v1.0
2. PRD v1.0

Visual:
1. Penpot `22 — UI High-Fidelity Final`
2. Penpot `22.5 — Style Guide`

Jika visual dan behavior tampak bertentangan, jangan menebak. Periksa requirement terkait dan laporkan konflik.
