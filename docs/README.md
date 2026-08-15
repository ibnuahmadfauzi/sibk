# Dokumentasi Aplikasi BK

Folder ini menjadi sumber komunikasi pengembangan bagi tim manusia, Codex, dan seluruh model yang dijalankan melalui Google Antigravity.

## Urutan sumber kebenaran

Jika terdapat perbedaan isi, gunakan urutan berikut:

1. Keputusan terbaru yang telah disahkan di `docs/decisions/decision-log.md`.
2. Kebutuhan teknis dan kriteria penerimaan di `docs/product/srs.md`.
3. Tujuan, prioritas, dan batas produk di `docs/product/prd.md`.
4. Hak akses di `docs/security/access-matrix.md`.
5. Inventaris halaman dan field di `docs/product/`.
6. Wireframe yang telah disetujui dan dipetakan di `docs/design/`.
7. Keputusan UX di `docs/ux/decision-log.md`.
8. Implementasi kode yang sedang berjalan.

Jika dua sumber pada tingkat yang sama bertentangan, jangan memilih diam-diam. Catat konflik dan hentikan bagian yang terdampak sampai keputusan dibuat.

## Peta dokumen

| Area | Dokumen | Fungsi |
|---|---|---|
| Orientasi agen | `ai/00-start-here.md` | Titik mulai dan jalur baca setiap tugas. |
| Pemuatan konteks | `ai/context-loading.md` | Aturan membaca dokumen secara selektif dan hemat token. |
| Perilaku agen | `ai/agent-operating-rules.md` | Aturan kerja lintas model dan pembagian kepakaran. |
| Brief tugas | `ai/task-brief-template.md` | Format seragam untuk menyerahkan pekerjaan. |
| Produk | `product/prd.md` | Tujuan, pengguna, ruang lingkup, dan prioritas. |
| Spesifikasi | `product/srs.md` | Kebutuhan, akses, data, integrasi, dan penerimaan. |
| Antarmuka | `product/ui-inventory.md` | Daftar 26 antarmuka MVP. |
| Field dan aksi | `product/ui-field-actions.md` | Katalog data, kontrol, validasi, respons, dan sensitivitas. |
| Hak akses | `security/access-matrix.md` | Batas tindakan per peran dan objek. |
| Sumber desain | `design/wireframe-source.md` | Lokasi Penpot dan aturan penggunaan sumber. |
| Pemetaan desain | `design/wireframe-page-map.md` | Pemetaan 26 halaman ke frame dan ekspor. |
| Transformasi desain | `design/README.md` | Batas antara struktur wireframe dan UI produksi. |
| Kualitas UI | `design/ui-quality-bar.md` | Standar daya tarik, komposisi, dan mutu visual. |
| Keputusan | `decisions/decision-log.md` | Keputusan proyek yang telah dikunci. |
| Validasi terbuka | `decisions/open-validation.md` | Keputusan domain dan teknis yang belum lengkap. |
| UX | `ux/decision-log.md` | Keputusan ahli UX dan alasan penerapannya. |
| Frontend | `frontend/README.md` | Ruang kerja frontend aktif. |
| Backend | `backend/README.md` | Batas dan aturan backend untuk tahap berikutnya. |
| Proses tim | `development/workflow.md` | Alur Git, implementasi, review, dan handoff. |
| Selesai | `development/definition-of-done.md` | Gerbang kualitas sebelum merge. |

## Aturan pemeliharaan

- Jangan mengubah PRD atau SRS hanya untuk menyesuaikan kode yang sudah dibuat.
- Setiap keputusan baru dicatat terlebih dahulu pada decision log yang sesuai.
- Setiap perubahan kebutuhan harus memperbarui dokumen turunan yang terdampak.
- Dokumen harus menjelaskan keadaan yang berlaku, bukan percakapan, opini model, atau rencana yang sudah tidak digunakan.
- Tautan harus relatif terhadap repository agar tetap dapat dibaca di GitHub dan oleh agen lokal.
