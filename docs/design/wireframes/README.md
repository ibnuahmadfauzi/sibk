# Ekspor Wireframe

Folder ini menyimpan ekspor wireframe agar dapat dibaca oleh agen yang tidak memiliki sesi atau akses langsung ke Penpot.

## Aturan ekspor

- Gunakan PNG untuk satu frame dan PDF hanya bila satu alur memerlukan beberapa halaman berurutan.
- Gunakan resolusi yang membuat label, field, anotasi, dan hubungan antarelemen tetap terbaca.
- Nama file harus mengikuti `docs/design/wireframe-page-map.md`.
- Jangan memasukkan data nyata murid, kredensial, atau informasi sensitif pada ekspor.
- Jangan menambahkan suffix tanggal pada nama file utama. Riwayat perubahan dikelola Git.
- Ekspor ulang file yang sama setelah perubahan wireframe disahkan.

## Kondisi paket saat ini

Paket dokumentasi menetapkan sumber Penpot, nama frame kanonik, dan nama file ekspor. Berkas PNG/PDF belum disertakan karena belum diekspor dari Penpot pada workspace ini. Sampai ekspor tersedia, agen harus membuka sumber Penpot dan tidak boleh mengklaim seluruh informasi wireframe telah tercakup bila sumber tersebut tidak dapat diakses.

## Pemeriksaan sebelum commit

- Semua halaman yang dikerjakan memiliki ekspor sesuai peta.
- Teks pada ekspor terbaca pada zoom normal.
- Ekspor sesuai frame Penpot terbaru.
- Tidak ada data pribadi atau rahasia.
- Informasi dan fungsi pada ekspor tetap konsisten dengan inventaris dan requirement.
