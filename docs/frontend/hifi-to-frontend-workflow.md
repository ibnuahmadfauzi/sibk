---
document_id: hifi-to-frontend-workflow
status: active
last_updated: 2026-08-16
---

# Workflow High-Fidelity ke Frontend

## Tujuan dan batas sumber

Workflow aktif frontend memakai board atau ekspor Penpot halaman yang berstatus `hifi-approved` sebagai satu-satunya sumber visual. Brief `PG-*` tetap menjadi sumber cakupan UX, state, peran, dan batas akses. Low-fidelity hanya boleh dipakai untuk mengklarifikasi maksud UX ketika diperlukan; ia tidak boleh diterjemahkan langsung menjadi UI produksi. Referensi `visual-direction-approved` hanya memberi arah umum dan bukan pengganti high-fidelity.

Jangan mulai implementasi visual, memilih nilai visual final, atau menyatakan sebuah halaman frontend siap bila paket high-fidelity halaman tersebut belum `hifi-approved`. Bila board, ekspor, foundations, state, peran, aset, atau token belum tersedia, catat kekurangannya dan kembalikan pekerjaan ke tahap desain.

## Alur aktif

```text
Audit repository → pilih PG → periksa hifi-approved → petakan komponen/tokens → implementasi → verifikasi teknis → manual-visual-review-pending → review manusia → implemented
```

### 1. Audit repository

- Periksa stack, versi Bootstrap, entrypoint CSS/JS, routing, komponen bersama, token terpusat, dan perintah format/lint/build/test yang benar-benar tersedia.
- Pertahankan pola sehat repository; jangan menambah framework UI paralel atau menggandakan komponen bersama.
- Catat ketidaksesuaian antara dokumentasi dan repository sebelum mengubah implementasi.

### 2. Pilih `PG-*`

- Pilih satu halaman menurut [urutan implementasi](page-implementation-order.md).
- Muat brief `docs/design/pages/PG-xxx-*.md`, baris yang sesuai pada [peta desain](../design/design-page-map.md), dan sumber high-fidelity yang dirujuk.
- Identifikasi semua state dan variasi peran dalam brief serta batas akses yang harus dipertahankan.

### 3. Periksa gerbang `hifi-approved`

- Pastikan peta/brief menunjukkan `hifi-approved`, nama board kanonik, serta board atau ekspor yang dapat diperiksa.
- Pastikan paket memuat referensi desktop, tablet, mobile, state, peran, komponen, token, dan aset yang diwajibkan.
- Hentikan implementasi visual bila salah satu bukti tersebut belum tersedia. Jangan memakai low-fi, screenshot, atau nilai perkiraan sebagai fallback.

### 4. Petakan komponen dan token

- Inventarisasi komponen Bootstrap dan komponen bersama yang sudah ada sebelum membuat komponen baru.
- Petakan foundations high-fidelity ke design tokens/CSS variables terpusat sesuai [aturan Bootstrap dan token](bootstrap-and-design-tokens.md).
- Gunakan Bootstrap sebagai fondasi struktur dan interaksi. Jangan hardcode nilai visual berulang, memakai inline style, atau membuat override halaman yang menduplikasi pola bersama.
- Gunakan fixture dan data review sintetis; jangan membuat kontrak backend atau data murid nyata dari asumsi.

### 5. Implementasi

- Bangun atau perbaiki fondasi dan komponen bersama yang benar-benar dibutuhkan, kemudian rangkai halaman.
- Terapkan state, responsif, aksesibilitas, dan variasi peran yang tercantum pada brief/high-fidelity.
- Jangan mengubah kebijakan domain, hak akses backend, atau kontrak data di luar tugas frontend.

### 6. Verifikasi teknis oleh agen

Agen menjalankan hanya pemeriksaan teknis yang relevan dan tersedia pada stack/repository: formatter, linter, build, test, pemeriksaan route/import/komponen, serta pemeriksaan aksesibilitas statis. Laporkan perintah dan hasil aktualnya.

Agen tidak otomatis membuka browser, mengambil screenshot, membandingkan gambar, atau menjalankan visual regression. Kegiatan tersebut hanya boleh dilakukan bila pengguna memintanya secara eksplisit.

### 7. Handoff `manual-visual-review-pending`

Setelah pemeriksaan teknis lulus, handoff agen harus berstatus `manual-visual-review-pending`. Handoff mencantumkan `PG-*`, status desain awal, board/ekspor sumber, komponen/token yang dipetakan, state/peran dalam cakupan, data sintetis yang dipakai, pemeriksaan teknis, dan risiko terbuka. Status ini bukan klaim bahwa tampilan sudah selesai secara visual.

### 8. Review manusia dan status akhir

Reviewer manusia mengikuti [prosedur review visual manual](manual-visual-review.md). Owner atau reviewer manusia yang ditentukan mencatat hasil review dan hanya mereka yang mengubah status menjadi `implemented` setelah seluruh koreksi visual yang diperlukan selesai.

## Rujukan

- [Sumber high-fidelity](../design/high-fidelity-source.md)
- [Bahasa visual](../design/visual-language.md)
- [Standar kualitas UI](../design/ui-quality-bar.md)
- [Checklist QA frontend](qa-checklist.md)
