# Checklist QA Frontend

Checklist ini digunakan sebelum perubahan frontend dinyatakan selesai atau diajukan ke pull request.

## Keterlacakan

- [ ] ID halaman `PG-*` tercantum pada brief atau PR.
- [ ] Frame Penpot atau file ekspor yang digunakan tercantum.
- [ ] Requirement SRS terkait telah diperiksa.
- [ ] Hak akses cocok dengan access matrix.
- [ ] Keputusan UX baru sudah dicatat.
- [ ] Keputusan domain yang belum sah tidak dihardcode.

## Transformasi wireframe

- [ ] UI bukan sekadar perubahan warna, radius, atau bayangan dari wireframe.
- [ ] Informasi, field, fungsi, dan aksi wajib tetap terjaga.
- [ ] Pengelompokan, prioritas, navigasi, dan alur UX wireframe telah diperiksa.
- [ ] Layout tidak di-pixel-copy dan telah disempurnakan oleh ahli UI tanpa mengabaikan maksud UX.
- [ ] Perbedaan wireframe dan UI produksi memiliki alasan UX atau teknis yang jelas.
- [ ] Perubahan struktur besar telah dicatat pada UX decision log.
- [ ] Layout menggunakan grid, alignment, spacing, dan kepadatan yang layak untuk produksi.
- [ ] Komponen, interaksi, state, dan variasi peran telah dilengkapi.
- [ ] Informasi atau aksi wajib pada inventaris tidak hilang karena wireframe lama belum memuatnya.

## Kualitas visual

- [ ] Halaman memiliki fokus visual dan tujuan yang segera dikenali.
- [ ] Komposisi terlihat dirancang untuk tugas halaman, bukan template dashboard generik.
- [ ] Hierarki, spacing, alignment, dan kepadatan informasi terasa seimbang.
- [ ] Tipografi dan ikon digunakan konsisten.
- [ ] Aksi utama jelas tanpa mendominasi berlebihan.
- [ ] Efek Soft UI membantu pengelompokan dan tidak mengurangi keterbacaan.
- [ ] UI tetap menarik, utuh, dan mudah dipindai pada mobile maupun desktop.

## Bootstrap dan komponen

- [ ] Komponen Bootstrap digunakan sebelum CSS khusus.
- [ ] Tidak ada framework UI paralel.
- [ ] Tidak ada inline style.
- [ ] Tidak ada warna, radius, atau shadow langsung di file halaman.
- [ ] Nilai visual berasal dari token terpusat.
- [ ] Pola berulang menggunakan komponen bersama.
- [ ] Tidak ada override `!important` tanpa alasan terdokumentasi.

## Layout dan responsif

- [ ] Mobile, tablet, dan desktop telah diperiksa.
- [ ] Tidak ada overflow horizontal yang tidak disengaja.
- [ ] Sidebar/offcanvas tidak menutup konten atau fokus.
- [ ] Tabel memiliki pola mobile yang jelas.
- [ ] Form tetap terbaca dan dapat digunakan pada layar sempit.
- [ ] Zoom 200 persen tidak menghilangkan fungsi.

## State

- [ ] Default.
- [ ] Loading.
- [ ] Data kosong.
- [ ] Filter tanpa hasil.
- [ ] Berhasil.
- [ ] Validasi gagal.
- [ ] Gangguan sistem/jaringan.
- [ ] Disabled bila relevan.
- [ ] Read-only bila relevan.
- [ ] Akses ditolak bila relevan.

## Aksesibilitas

- [ ] Heading tersusun berurutan.
- [ ] Elemen semantik digunakan.
- [ ] Semua kontrol memiliki label/nama aksesibel.
- [ ] Seluruh fungsi dapat dijalankan dengan keyboard.
- [ ] Fokus terlihat dan tidak terperangkap.
- [ ] Modal mengelola fokus dengan benar.
- [ ] Pesan error terhubung ke field.
- [ ] Informasi tidak bergantung pada warna saja.
- [ ] Kontras teks, border penting, dan focus ring memadai.

## Peran dan sensitivitas

- [ ] Guru BK hanya melihat scope yang diizinkan pada UI.
- [ ] Koordinator tidak memperoleh tampilan sensitif hanya karena jabatannya.
- [ ] Waka memiliki penanda read-only dan tidak melihat aksi mutasi.
- [ ] Waka hanya melihat detail kasus terkoordinasi.
- [ ] Admin IT tidak melihat isi layanan BK.
- [ ] Data sensitif tidak muncul pada URL, log, notifikasi umum, atau fixture.

## Validasi teknis

- [ ] Formatter lulus.
- [ ] Linter lulus.
- [ ] Test terkait lulus.
- [ ] Build produksi lulus.
- [ ] Tidak ada error atau warning baru pada console yang berasal dari perubahan.
- [ ] Pemeriksaan visual tidak menunjukkan overlap, clipping, atau komponen rusak.

## Dokumentasi dan handoff

- [ ] Dokumentasi komponen atau alur diperbarui bila perlu.
- [ ] Kontrak data/mocking dijelaskan.
- [ ] File yang berubah dan hasil pemeriksaan dicantumkan pada handoff.
- [ ] Risiko yang belum selesai dijelaskan tanpa menyatakan tugas selesai secara palsu.
