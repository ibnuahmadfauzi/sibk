# Frontend Aplikasi BK

## Rute aktif

Frontend menerapkan board atau ekspor Penpot `hifi-approved` dengan Bootstrap dan design tokens/CSS variables terpusat. Low-fidelity yang disetujui adalah kontrak UX, bukan sumber visual frontend; visual direction hanya inspirasi terkontrol. Jangan mulai implementasi visual halaman sebelum status `hifi-approved` beserta paket high-fidelity-nya tersedia.

Ikuti [workflow high-fidelity ke frontend](hifi-to-frontend-workflow.md): audit repository, pilih `PG-*`, periksa `hifi-approved`, petakan komponen/tokens, implementasi, verifikasi teknis, handoff `manual-visual-review-pending`, review manusia, lalu `implemented`.

## Paket konteks satu halaman

Konteks kerja awal per halaman maksimal empat sumber:

1. Brief `docs/design/pages/PG-xxx-*.md`.
2. Board atau ekspor halaman `hifi-approved` yang dirujuk brief.
3. [Aturan Bootstrap dan token](bootstrap-and-design-tokens.md).
4. Maksimal satu pedoman aksesibilitas atau UX yang relevan dengan halaman.

Gunakan [design page map](../design/design-page-map.md) dan [sumber high-fidelity](../design/high-fidelity-source.md) hanya sebagai selector untuk menemukan atau memvalidasi board/ekspor yang tepat. Setelah sumber halaman terpilih dan tervalidasi, lepaskan kedua selector tersebut dari konteks kerja; keduanya bukan tambahan paket kerja per halaman.

Jangan memakai board low-fi, workflow lama, atau gambar visual direction sebagai rute visual aktif ketika board/ekspor high-fidelity tersedia.

## Aturan implementasi

- Audit versi Bootstrap, pola stack, routing, komponen bersama, token, dan pemeriksaan tersedia sebelum mengubah kode.
- Gunakan Bootstrap serta komponen yang sudah ada sebelum membuat CSS atau komponen khusus; jangan menggandakan pola bersama.
- Ambil nilai visual dari foundations `hifi-approved` melalui token/CSS variables terpusat. Jangan hardcode nilai berulang, menyebarkan hex/rgb, atau memakai inline style pada halaman.
- Gunakan fixture sintetis; jangan menetapkan kontrak backend, kebijakan domain, atau hak akses dari asumsi frontend.
- Implementasikan state, responsif, aksesibilitas, dan variasi peran yang tercantum pada brief dan high-fidelity.

## QA dan handoff

Agen menjalankan pemeriksaan teknis yang relevan dan tersedia: format/lint/build/test serta pemeriksaan route, komponen, dan aksesibilitas statis. Agen tidak otomatis membuka browser, mengambil screenshot, membandingkan gambar, atau menjalankan visual regression; lakukan hanya bila pengguna memintanya eksplisit.

Setelah pemeriksaan teknis, handoff wajib `manual-visual-review-pending`, bukan klaim visually complete. Reviewer manusia memakai [review visual manual](manual-visual-review.md) pada desktop referensi, tablet `768px`, mobile `390px`, serta seluruh state/peran brief. Hanya reviewer manusia/owner yang ditentukan dapat mengubah status menjadi `implemented`.

Gunakan [checklist QA frontend](qa-checklist.md) dan [urutan implementasi halaman](page-implementation-order.md) sebagai pelengkap rute aktif ini.
