# Checklist QA Frontend

Gunakan checklist ini setelah halaman memulai dari board atau ekspor `hifi-approved`. Agen hanya boleh mencentang **Pemeriksaan agen**. Bagian visual dan persetujuan akhir adalah tanggung jawab reviewer manusia/owner yang ditentukan.

## Pemeriksaan agen

### Keterlacakan dan gerbang

- [ ] `PG-*`, brief, dan sumber board/ekspor high-fidelity dicantumkan.
- [ ] Status desain halaman telah diverifikasi sebagai `hifi-approved` sebelum implementasi visual.
- [ ] State, variasi peran, batas akses, dan requirement dalam brief telah dipetakan.
- [ ] Low-fi hanya dipakai untuk klarifikasi maksud UX, bukan sumber visual; visual direction bukan pengganti high-fidelity.
- [ ] Data fixture dan bukti teknis bersifat sintetis.

### Arsitektur frontend

- [ ] Bootstrap yang tersedia dipakai sebelum CSS/komponen khusus.
- [ ] Token/CSS variables terpusat dipakai; tidak ada nilai visual berulang yang dihardcode atau inline style.
- [ ] Nilai token contoh yang belum berasal dari foundations approved ditandai `provisional`.
- [ ] Komponen bersama digunakan kembali; pola bersama tidak diduplikasi.
- [ ] Implementasi mencakup state, variasi peran, responsif, semantik, label, fokus, dan aksesibilitas statis yang relevan.

### Pemeriksaan teknis

- [ ] Formatter dijalankan bila tersedia.
- [ ] Linter dijalankan bila tersedia.
- [ ] Build dan test relevan dijalankan bila tersedia.
- [ ] Route, import, dan komponen yang berubah diperiksa sesuai stack.
- [ ] Pemeriksaan aksesibilitas statis yang tersedia dijalankan.
- [ ] Hasil aktual, kegagalan, dan pemeriksaan yang tidak tersedia dicatat pada handoff.
- [ ] Handoff berstatus `manual-visual-review-pending`.

Agen tidak otomatis membuka browser, mengambil screenshot, membandingkan gambar, atau menjalankan visual regression. Kegiatan tersebut hanya atas permintaan eksplisit pengguna.

## Pemeriksaan visual manual

Hanya reviewer manusia yang mencentang bagian ini, dengan acuan board/ekspor `hifi-approved` dan data sintetis.

- [ ] Desktop pada viewport referensi high-fidelity diperiksa.
- [ ] Tablet pada `768px` diperiksa.
- [ ] Mobile pada `390px` diperiksa.
- [ ] Semua state dan variasi peran pada brief diperiksa.
- [ ] Struktur, hierarki, tipografi, spacing, warna, border, radius, shadow, ikon, aset, kepadatan, dan overflow sesuai acuan.
- [ ] Fokus, label, kontras, responsif, serta aksi yang terlihat sesuai peran diperiksa.
- [ ] Temuan memakai format: PG, viewport, komponen, gejala, hasil yang diharapkan.
- [ ] Bukti review/screenshot, bila dibuat, memakai data sintetis.

## Persetujuan akhir

- [ ] Reviewer manusia mencatat hasil review visual dan seluruh koreksi yang diperlukan.
- [ ] Owner atau reviewer manusia yang ditentukan menyetujui halaman.
- [ ] Owner atau reviewer manusia yang ditentukan mengubah status menjadi `implemented`.

Halaman tidak boleh dinyatakan selesai secara visual sebelum bagian ini tercatat. Lihat [prosedur review visual manual](manual-visual-review.md).
