# Standar Kualitas Visual UI

## Acuan dan wewenang

Untuk frontend, board atau ekspor Penpot halaman `hifi-approved` adalah acuan visual. Low-fidelity approved tetap mengunci informasi, alur, aksi, state, hierarki, dan batas peran, tetapi tidak menjadi sumber visual langsung. Gambar visual direction hanya mengarahkan karakter umum dan tidak dapat menggantikan high-fidelity.

Agen frontend menerapkan acuan tersebut dengan Bootstrap, komponen bersama, dan design tokens/CSS variables terpusat. Agen boleh menyelesaikan detail teknis yang telah ditetapkan acuan, tetapi tidak mendesain ulang halaman, menebak nilai visual permanen, atau mengklaim kesesuaian visual. Nilai contoh token yang belum berasal dari foundations approved bersifat `provisional`.

## Karakter visual

UI Aplikasi BK harus terasa:

- tenang dan dapat dipercaya;
- modern tanpa mengikuti tren secara berlebihan;
- profesional tetapi tidak kaku;
- ramah bagi Guru BK, Koordinator, Waka, dan Admin IT;
- rapi pada halaman dengan informasi padat; dan
- aman untuk konteks layanan dan data sensitif murid.

Tampilan tidak boleh terasa menghukum, menakutkan, terlalu dekoratif, atau menyerupai template dashboard generik yang hanya mengganti logo dan warna. Soft UI hanya memberi aksen fungsional pada permukaan, kartu, dan navigasi; input, tabel, status, dan fokus tetap tegas serta mudah dibaca.

## Prinsip implementasi

### Hierarki dan layout

- Tujuan halaman, konteks, informasi penting, dan aksi utama harus memiliki penekanan yang sesuai board high-fidelity.
- Gunakan grid Bootstrap, alignment, dan komponen bersama; jangan memosisikan ulang nilai visual yang sama pada tiap halaman.
- Kepadatan, grouping, whitespace, tabel, kartu, tab, dan progressive disclosure mengikuti high-fidelity tanpa mengubah kontrak UX atau batas akses.

### Token, warna, dan aset

- Semua nilai visual berasal dari token terpusat yang dipetakan dari foundations `hifi-approved`; nilai yang belum demikian tetap `provisional`.
- Warna status selalu menyampaikan makna bersama teks, ikon, atau pola lain.
- Gunakan logo, ikon, dan ilustrasi proyek yang disahkan; jangan menggambar ulang aset dengan CSS.
- Hindari gradient, shadow berat, warna aksen, dan dekorasi yang mengurangi keterbacaan atau kontras.

### Responsif, state, dan peran

- Terapkan desktop referensi, tablet `768px`, dan mobile `390px` beserta seluruh state/peran yang diwajibkan brief.
- Layout mobile bukan desktop yang diperkecil; tabel dan aksi harus tetap dapat dipahami tanpa menutupi konten.
- Fokus, label, error, disabled, loading, empty, read-only, dan akses ditolak harus tersedia bila dicakup brief/high-fidelity.

## Review visual manual

Kesesuaian visual terhadap high-fidelity dinilai oleh reviewer manusia, bukan oleh agen melalui browser otomatis, screenshot, perbandingan gambar, atau visual regression. Setelah pemeriksaan teknis, agen menyerahkan `manual-visual-review-pending` dan melaporkan sumbernya. Browser, screenshot, perbandingan gambar, atau visual regression hanya dilakukan agen bila pengguna memintanya eksplisit.

Reviewer manusia memeriksa desktop referensi, tablet `768px`, mobile `390px`, dan semua state/peran brief dengan data sintetis. Temuan mencantumkan `PG`, viewport, komponen, gejala, dan hasil yang diharapkan. Hanya reviewer manusia atau owner yang ditentukan yang boleh menyetujui tampilan dan mengubah status menjadi `implemented`; prosedur lengkapnya ada di [manual visual review](../frontend/manual-visual-review.md).
