# Dokumentasi Aplikasi BK

Folder ini menjadi sumber komunikasi pengembangan bagi tim manusia, Codex, dan seluruh model yang dijalankan melalui Google Antigravity.

## Urutan sumber kebenaran

Jika terdapat perbedaan isi, gunakan urutan berikut:

1. Keputusan terbaru yang telah disahkan di `docs/decisions/decision-log.md`.
2. Kebutuhan teknis dan kriteria penerimaan di `docs/product/srs.md`.
3. Tujuan, prioritas, dan batas produk di `docs/product/prd.md`.
4. Hak akses di `docs/security/access-matrix.md`.
5. Inventaris halaman dan field di `docs/product/`.
6. [Pipeline delivery desain](design/design-delivery-pipeline.md): low-fidelity approved adalah kontrak UX, sedangkan high-fidelity approved adalah kontrak visual frontend.
7. [Keputusan UX](ux/decision-log.md).
8. Implementasi kode yang sedang berjalan.

Jika dua sumber pada tingkat yang sama bertentangan, jangan memilih diam-diam. Catat konflik dan hentikan bagian yang terdampak sampai keputusan dibuat.

## Peta dokumen

| Area | Dokumen | Fungsi |
|---|---|---|
| Orientasi agen | [Mulai dari sini](ai/00-start-here.md) | Titik mulai dan jalur baca setiap tugas. |
| Pemuatan konteks | [Aturan konteks](ai/context-loading.md) | Aturan membaca dokumen secara selektif dan hemat token. |
| Perilaku agen | [Aturan operasi](ai/agent-operating-rules.md) | Aturan kerja lintas model dan pembagian kepakaran. |
| Brief tugas | [Template brief](ai/task-brief-template.md) | Format seragam untuk menyerahkan pekerjaan. |
| Produk | [PRD](product/prd.md) | Tujuan, pengguna, ruang lingkup, dan prioritas. |
| Spesifikasi | [SRS](product/srs.md) | Kebutuhan, akses, data, integrasi, dan penerimaan. |
| Antarmuka | [Inventaris UI](product/ui-inventory.md) | Daftar 26 antarmuka MVP. |
| Field dan aksi | [Katalog field dan aksi](product/ui-field-actions.md) | Data, kontrol, validasi, respons, dan sensitivitas. |
| Hak akses | [Access matrix](security/access-matrix.md) | Batas tindakan per peran dan objek. |
| Pipeline desain | [Design delivery pipeline](design/design-delivery-pipeline.md) | Rantai low-fi → high-fi → frontend dan status gerbang desain. |
| Sumber high-fidelity | [High-fidelity source](design/high-fidelity-source.md) | Board/ekspor dan paket visual resmi per halaman. |
| Peta halaman desain | [Design page map](design/design-page-map.md) | Pemetaan brief, low-fi, high-fi, ekspor, dan status per `PG-*`. |
| Bahasa visual | [Visual language](design/visual-language.md) | Karakter visual dan batas penggunaan visual direction. |
| Brief halaman | [Daftar page briefs](design/pages/README.md) | Paket konteks ringkas per halaman `PG-*`. |
| Keputusan | [Decision log](decisions/decision-log.md) | Keputusan proyek yang telah dikunci. |
| Validasi terbuka | [Open validation](decisions/open-validation.md) | Keputusan domain dan teknis yang belum lengkap. |
| UX | [UX decision log](ux/decision-log.md) | Keputusan ahli UX dan alasan penerapannya. |
| Frontend | [Frontend README](frontend/README.md) | Ruang kerja frontend aktif dan batas sumbernya. |
| Implementasi frontend | [Workflow high-fidelity ke frontend](frontend/hifi-to-frontend-workflow.md) | Handoff teknis dari `hifi-approved` hingga review manusia. |
| Review visual manual | [Prosedur review visual manual](frontend/manual-visual-review.md) | Review manusia, bukti sintetis, dan persetujuan status akhir. |
| Backend | [Backend README](backend/README.md) | Batas dan aturan backend untuk tahap berikutnya. |
| Proses tim | [Workflow pengembangan](development/workflow.md) | Alur Git, area kerja, handoff, review, dan merge. |
| Selesai | [Definition of Done](development/definition-of-done.md) | Gerbang kualitas dan persetujuan sebelum merge. |

## Aturan pemeliharaan

- Jangan mengubah PRD atau SRS hanya untuk menyesuaikan kode yang sudah dibuat.
- Setiap keputusan baru dicatat terlebih dahulu pada decision log yang sesuai.
- Setiap perubahan kebutuhan harus memperbarui dokumen turunan yang terdampak.
- Dokumen harus menjelaskan keadaan yang berlaku, bukan percakapan, opini model, atau rencana yang sudah tidak digunakan.
- Tautan harus relatif terhadap repository agar tetap dapat dibaca di GitHub dan oleh agen lokal.
