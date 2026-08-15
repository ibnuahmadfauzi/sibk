# Aturan Kontrak API

Dokumen ini berlaku ketika backend atau integrasi frontend mulai dikerjakan.

## Prinsip kontrak

- Satu endpoint memiliki tujuan yang jelas.
- Request dan response memakai nama field konsisten dengan SRS dan data referensi.
- Response hanya memuat data yang dibutuhkan pengguna dan diizinkan peran.
- Otorisasi diperiksa sebelum data sensitif dibaca, dihitung, atau diserialisasi.
- Kontrak tidak bergantung pada label visual; gunakan kode/status stabil dari sumber sah.
- Field domain yang belum disahkan tidak boleh menjadi enum permanen dalam kode.

## Struktur minimum yang harus didokumentasikan

- method dan path;
- tujuan dan ID requirement;
- peran serta kondisi akses;
- parameter path/query;
- request body dan validasi;
- response berhasil;
- error yang mungkin terjadi;
- field sensitif yang dikecualikan;
- pagination, sorting, dan filter;
- side effect dan audit event;
- idempotensi bila tindakan dapat dikirim ulang.

## Status HTTP

- `200` untuk pembacaan atau perubahan yang berhasil dan memiliki body.
- `201` untuk pembuatan resource.
- `204` untuk hasil berhasil tanpa body bila sesuai pola repository.
- `400` untuk request yang tidak dapat diproses secara umum.
- `401` untuk sesi tidak sah.
- `403` untuk pengguna sah yang tidak memiliki kewenangan.
- `404` dapat digunakan untuk mencegah kebocoran keberadaan objek di luar scope sesuai kebijakan keamanan.
- `409` untuk konflik state, duplikasi, atau rekonsiliasi.
- `422` untuk validasi field bila sesuai pola framework.
- `429` dan `5xx` ditangani dengan pesan aman serta correlation/request ID bila tersedia.

Ikuti pola error repository yang sudah ada; jangan membuat format kedua tanpa alasan.

## Otorisasi wajib diuji

- Guru BK pada murid dalam dan luar scope.
- Koordinator dengan dan tanpa scope Guru BK.
- Waka pada kasus terkoordinasi dan tidak terkoordinasi.
- Waka terhadap seluruh aksi mutasi.
- Admin IT terhadap fungsi teknis dan isi layanan BK.
- Pemegang scope baru terhadap histori lama dan aksi edit catatan lama.
- Akses langsung melalui ID, URL, filter, ekspor, serta endpoint laporan.

## Integrasi

- e-Tatib selalu baca-saja pada MVP.
- Dapodik tetap master identitas dan kelas.
- NISN menjadi kunci pencocokan identitas sementara.
- Gangguan integrasi menghasilkan status yang dapat ditampilkan UI tanpa menampilkan rahasia teknis.
- Retry, timeout, mapping, dan rekonsiliasi mengikuti keputusan Admin IT yang telah disahkan.
