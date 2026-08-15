# Mulai dari Sini

Dokumen ini hanya router konteks. Jangan membaca seluruh dokumentasi untuk setiap tugas.

## Proyek dan tahap aktif

Aplikasi BK adalah ruang kerja layanan BK SMKN 1 Surabaya untuk kasus, tindak lanjut, histori murid, koordinasi Waka, laporan, dan audit. Dapodik tetap menjadi master murid/kelas; e-Tatib tetap menjadi sumber baca-saja pelanggaran dan poin.

Tahap aktif adalah frontend dari wireframe menuju UI produksi. Bootstrap dan design tokens menjadi fondasi. Frontend dan backend berada dalam satu repository, tetapi tidak boleh dicampur tanpa cakupan eksplisit.

## Cara memilih konteks

1. Tentukan area tugas: frontend, backend, dokumentasi, atau lintas area.
2. Tentukan ID halaman dan requirement yang terdampak.
3. Cari ID pada dokumentasi, misalnya:

   ```bash
   rg -n "PG-103|CASE-12|AUTH-05" docs/
   ```

4. Baca baris/bagian yang ditemukan dan dokumen rute di bawah.
5. Tambahkan dokumen lain hanya jika muncul dependensi atau konflik.

Aturan rinci pemuatan konteks: `docs/ai/context-loading.md`.

## Rute berdasarkan tugas

### Frontend satu halaman

Wajib:

- `docs/frontend/README.md`
- baris `PG-*` pada `docs/product/ui-inventory.md`
- baris halaman terkait pada `docs/product/ui-field-actions.md`
- frame/ekspor melalui `docs/design/wireframe-page-map.md`

Tambahkan hanya bila relevan:

- visual: `docs/design/ui-quality-bar.md` dan `docs/frontend/bootstrap-and-design-tokens.md`
- pola UX/state: `docs/frontend/ui-ux-guidelines.md`
- akses/sensitivitas: `docs/security/access-matrix.md`
- keputusan atau konflik: `docs/decisions/decision-log.md`, `docs/ux/decision-log.md`, atau `docs/decisions/open-validation.md`
- QA/handoff: `docs/frontend/qa-checklist.md`

### Fondasi atau lintas halaman frontend

Baca `docs/design/README.md`, `docs/frontend/wireframe-to-ui-workflow.md`, dan dokumen visual/UX terkait. Tambahkan inventaris hanya untuk pola atau halaman yang terdampak.

### Backend

Baca `docs/backend/README.md`, requirement SRS terkait, access matrix terkait, lalu `docs/backend/api-contract-rules.md` bila mengubah kontrak API.

### Dokumentasi atau review

Baca `docs/development/documentation-rules.md` dan sumber kanonik yang benar-benar terdampak. Gunakan `docs/development/definition-of-done.md` saat menilai kesiapan merge.

## Batas keputusan

Ketidakjelasan visual, responsif, state, dan interaksi dapat diputuskan sebagai UX lalu dicatat. Istilah BK, data wajib, status operasional, hak akses, verifikator, retensi, dan integrasi tidak boleh diputuskan melalui asumsi agen.
