# Mulai dari Sini

## Tujuan proyek

Aplikasi BK adalah ruang kerja terpusat untuk layanan Bimbingan dan Konseling di SMKN 1 Surabaya. Sistem menyatukan kasus, tindak lanjut, histori murid, koordinasi Waka Kesiswaan, laporan, serta audit tanpa menggantikan Dapodik atau e-Tatib.

## Tahap aktif

Tahap aktif adalah **pengembangan frontend dari wireframe menjadi UI**. Style guide visual masih dapat disesuaikan, sehingga seluruh nilai visual harus memakai token terpusat. Bootstrap menjadi fondasi layout dan komponen.

Frontend dan backend berada dalam satu repository, tetapi dikembangkan sebagai area kerja terpisah. Tugas frontend tidak boleh memperluas cakupan menjadi implementasi backend tanpa instruksi eksplisit.

## Pengguna MVP

- Guru BK
- Koordinator BK
- Waka Kesiswaan
- Admin IT

Wali kelas dan murid berada pada P1. Prestasi tetap P0 bertahap setelah fungsi inti stabil.

## Keputusan yang tidak boleh berubah diam-diam

- Koordinator BK menjadi penanggung jawab operasional.
- Jabatan Koordinator tidak otomatis membuka konsultasi sensitif.
- Waka hanya-baca dan hanya menerima detail kasus yang dikoordinasikan kepadanya.
- Guru BK pemegang scope aktif dapat membaca histori murid lintas kelas dan pergantian guru, tetapi tidak mengubah catatan profesional lama.
- Dapodik adalah sumber master murid, kelas, dan tahun ajaran.
- e-Tatib adalah sumber baca-saja pelanggaran dan poin; tidak ada write-back pada MVP.
- Sebelum sinkronisasi, kasus dapat memakai NISN dan nama sementara lalu direkonsiliasi berdasarkan NISN tanpa duplikasi.
- Perubahan penugasan dicatat Koordinator berdasarkan keputusan resmi; tidak ada rolling atau pemindahan kasus aktif otomatis.
- Admin IT menangani akun, infrastruktur, integrasi, dan rekonsiliasi tanpa memperoleh akses otomatis ke isi BK.
- Data disimpan minimum tiga tahun dan tidak dihapus otomatis sebelum kebijakan penghapusan serta pemulihan disahkan.

## Jalur baca berdasarkan tugas

### Semua tugas

1. `docs/decisions/decision-log.md`
2. Bagian PRD dan SRS yang terkait
3. `docs/security/access-matrix.md`
4. `docs/decisions/open-validation.md`

### Tugas frontend

1. `docs/frontend/README.md`
2. `docs/product/ui-inventory.md`
3. Baris halaman terkait pada `docs/product/ui-field-actions.md`
4. `docs/frontend/bootstrap-and-design-tokens.md`
5. `docs/frontend/ui-ux-guidelines.md`
6. `docs/frontend/wireframe-to-ui-workflow.md`
7. `docs/frontend/qa-checklist.md`
8. `docs/ux/decision-log.md`

### Tugas backend

1. `docs/backend/README.md`
2. `docs/backend/api-contract-rules.md`
3. Requirement SRS terkait
4. `docs/security/access-matrix.md`

### Tugas dokumentasi atau review

1. `docs/development/documentation-rules.md`
2. `docs/development/definition-of-done.md`

## Langkah awal setiap agen

1. Jalankan audit singkat berdasarkan `docs/development/repository-audit-checklist.md`.
2. Cocokkan tugas dengan ID halaman (`PG-*`) dan requirement (`AUTH-*`, `CASE-*`, dan seterusnya).
3. Tentukan apakah ketidakjelasan termasuk UX atau domain.
4. Buat rencana perubahan kecil yang dapat diperiksa.
5. Kerjakan, uji, periksa visual, lalu dokumentasikan hasil.
