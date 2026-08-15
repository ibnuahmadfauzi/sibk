# Standar Kualitas Visual UI

Dokumen ini menetapkan mutu visual minimum Aplikasi BK. Agen frontend bertindak sebagai ahli UI dan memiliki kewenangan menyempurnakan komposisi visual wireframe selama tetap menjaga maksud UX, kebutuhan, alur bisnis, hak akses, sensitivitas data, dan makna informasi.

## Karakter visual

UI Aplikasi BK harus terasa:

- tenang dan dapat dipercaya;
- modern tanpa mengikuti tren secara berlebihan;
- profesional tetapi tidak kaku;
- ramah bagi Guru BK, Koordinator, Waka, dan Admin IT;
- rapi pada halaman dengan informasi padat;
- aman untuk konteks layanan dan data sensitif murid.

Tampilan tidak boleh terasa menghukum, menakutkan, terlalu dekoratif, atau menyerupai template dashboard generik yang hanya mengganti logo dan warna.

## Wewenang ahli UI

Untuk meningkatkan mutu visual dan penggunaan, agen boleh:

- mengubah grid, posisi, urutan visual, alignment, dan pembagian kolom;
- mengelompokkan atau memisahkan informasi menjadi section, card, tab, accordion, drawer, atau modal;
- memilih pola tabel, daftar, timeline, kartu ringkasan, dan form yang paling sesuai;
- mengatur kepadatan informasi, whitespace, fokus visual, dan progressive disclosure;
- menentukan tipografi, skala teks, ikon, ilustrasi fungsional, border, radius, serta bayangan melalui token;
- membuat variasi layout untuk mobile, tablet, desktop, dan peran berbeda;
- menyederhanakan microcopy tanpa mengubah istilah resmi atau makna kebijakan.

Setiap perubahan tetap harus mempertahankan informasi, aksi, validasi, dan batas akses yang diwajibkan sumber produk.

Perubahan terhadap pengelompokan, prioritas, navigasi, atau alur wireframe bukan sekadar keputusan visual. Perubahan tersebut harus didasarkan pada masalah penggunaan yang jelas dan dicatat sebagai keputusan UX.

## Prinsip komposisi

### Hierarki

- Tujuan halaman dapat dikenali dalam beberapa detik.
- Judul, konteks, informasi penting, dan aksi utama memiliki tingkat penekanan yang berbeda.
- Satu konteks visual memiliki paling banyak satu aksi utama.
- Data sensitif tidak dijadikan pusat perhatian tanpa kebutuhan tugas.

### Layout dan ritme

- Gunakan grid Bootstrap sebagai fondasi, bukan posisi absolut dari wireframe.
- Jarak antarelemen membentuk kelompok informasi yang jelas.
- Kepadatan data disesuaikan dengan tugas; hindari halaman kosong berlebihan maupun tampilan sesak.
- Card digunakan untuk mengelompokkan konteks, bukan membungkus setiap elemen secara terpisah.
- Alignment dan ukuran komponen konsisten antarhalaman.

### Warna dan permukaan

- Semua nilai berasal dari design tokens.
- Warna utama membangun identitas; warna status hanya menyampaikan makna sistem.
- Gunakan arah sidebar biru dan menu aktif krem melalui token yang dapat diganti.
- Soft UI dibatasi pada permukaan, kartu, dan navigasi. Input, tabel, status, serta fokus harus tetap tegas.
- Hindari terlalu banyak gradient, shadow berat, warna aksen, atau efek dekoratif yang mengurangi keterbacaan.

### Tipografi dan ikon

- Skala tipografi harus konsisten dan membedakan judul, label, isi, metadata, serta bantuan.
- Gunakan panjang baris dan line-height yang nyaman dibaca.
- Ikon membantu pengenalan, bukan menggantikan label pada aksi penting.
- Gunakan satu keluarga/gaya ikon secara konsisten.

### Responsif

- Layout mobile bukan versi desktop yang diperkecil.
- Prioritaskan informasi dan aksi sesuai konteks layar.
- Tabel kompleks dapat berubah menjadi kolom prioritas, daftar, atau kartu dengan akses menuju detail.
- Aksi penting tetap mudah dijangkau tanpa menutupi konten.

## Gerbang penilaian visual

Sebelum halaman dinyatakan selesai, reviewer harus dapat menjawab “ya” untuk hal berikut:

- Apakah halaman memiliki fokus visual dan tujuan yang jelas?
- Apakah komposisinya terlihat dirancang khusus untuk tugas halaman tersebut?
- Apakah informasi dapat dipindai tanpa membaca seluruh layar?
- Apakah aksi utama mudah ditemukan tetapi tidak mendominasi berlebihan?
- Apakah tampilan konsisten dengan halaman lain tanpa terasa monoton?
- Apakah UI tetap menarik dan utuh pada mobile maupun desktop?
- Apakah efek visual membantu pemahaman dan tidak mengurangi kontras?
- Apakah pengguna dapat menyelesaikan tugas tanpa bergantung pada warna atau tebakan?

Kemiripan gaya visual dengan wireframe tidak menjadi ukuran kualitas. Kesesuaian terhadap maksud UX tetap dinilai bersama ketercakupan informasi, kemudahan penggunaan, konsistensi, daya tarik visual, aksesibilitas, dan kepatuhan terhadap kebutuhan.
