# UX Decision Log

Keputusan di bawah dapat diterapkan untuk menyelesaikan ketidakjelasan UX tanpa mengubah kebijakan BK. Nilai visual tetap dapat disesuaikan melalui token terpusat.

| ID | Keputusan UX | Alasan | Dampak implementasi | Status |
|---|---|---|---|---|
| UX-001 | Gunakan satu app shell konsisten: sidebar pada desktop, drawer pada layar sempit, top bar untuk konteks halaman dan aksi global. | Menjaga orientasi pada aplikasi dengan banyak modul dan peran. | Navigasi dibuat satu komponen bersama. | Disetujui ahli |
| UX-002 | Menu yang tidak tersedia bagi suatu peran tidak ditampilkan; permintaan langsung tetap menghasilkan respons akses ditolak yang aman. | Mengurangi kebingungan tanpa menjadikan UI sebagai lapisan keamanan. | Visibility UI dan otorisasi server diperiksa terpisah. | Disetujui ahli |
| UX-003 | Gunakan progressive disclosure pada form panjang dan data sensitif. Tampilkan informasi yang dibutuhkan untuk keputusan saat itu. | Mengurangi beban kognitif dan paparan data. | Bagian lanjutan memakai section, accordion, tab, atau dialog sesuai konteks. | Disetujui ahli |
| UX-004 | Setiap halaman data memiliki state loading, kosong, gagal, berhasil, read-only, dan akses ditolak bila relevan. | Wireframe kondisi ideal saja tidak cukup untuk implementasi nyata. | State menjadi bagian kriteria penerimaan setiap halaman. | Disetujui ahli |
| UX-005 | Validasi form ditampilkan dekat field dan menyediakan ringkasan saat beberapa error terjadi. Fokus berpindah ke error pertama. | Membantu pemulihan kesalahan dan akses keyboard. | Komponen validasi dipakai konsisten. | Disetujui ahli |
| UX-006 | Status tidak dibedakan hanya melalui warna; selalu sertakan teks, ikon, atau pola tambahan. | Menjaga aksesibilitas dan kejelasan. | Badge dan alert memiliki label eksplisit. | Disetujui ahli |
| UX-007 | Tabel kompleks tetap digunakan pada desktop, tetapi pada layar sempit menampilkan kolom prioritas atau representasi kartu tanpa menghilangkan akses ke detail. | Menjaga keterbacaan tanpa membuat gulir horizontal ekstrem. | Setiap tabel menetapkan kolom esensial dan pola mobile. | Disetujui ahli |
| UX-008 | Aksi utama maksimal satu per konteks visual. Aksi sekunder dan berisiko dipisahkan. | Mengurangi salah klik dan kompetisi visual. | Hierarki tombol Bootstrap diterapkan konsisten. | Disetujui ahli |
| UX-009 | Aksi yang mengubah status, menyelesaikan kasus, mengalihkan kasus, atau memengaruhi akses memerlukan konfirmasi kontekstual. | Dampaknya tinggi dan harus disadari pengguna. | Dialog menjelaskan objek, dampak, dan hasil tindakan. | Disetujui ahli |
| UX-010 | Halaman Waka menggunakan penanda read-only yang terlihat dan tidak menampilkan kontrol edit. | Mencegah ekspektasi tindakan yang tidak tersedia. | Gunakan badge/notice read-only pada detail kasus terkoordinasi. | Disetujui ahli |
| UX-011 | Detail sensitif dipisahkan secara visual dari ringkasan umum dan tidak dimuat tanpa kewenangan. | Mengurangi paparan serta memudahkan pembatasan per bagian. | Komponen dan respons data dibagi berdasarkan sensitivitas. | Disetujui ahli |
| UX-012 | Gunakan Soft UI secara terbatas pada permukaan, kartu, dan navigasi; input, fokus, tabel, alert, serta status harus tetap memiliki batas dan kontras jelas. | Neumorphism penuh berisiko mengaburkan affordance dan fokus. | Bayangan berasal dari token dan tidak menjadi satu-satunya pembeda komponen. | Disetujui ahli |
| UX-013 | Sidebar memakai arah warna biru dan menu aktif memakai latar krem; nilai warna tidak dikunci pada komponen. | Mempertahankan arah visual yang telah dipilih sekaligus memudahkan revisi style guide. | Seluruh nilai disimpan pada token tema. | Disetujui ahli |
| UX-014 | Filter aktif terlihat, dapat dihapus satu per satu, dan dipertahankan saat pengguna kembali dari halaman detail bila alur memungkinkan. | Mengurangi pengulangan saat meninjau banyak kasus atau murid. | State filter dikelola pada pola yang sesuai dengan stack. | Disetujui ahli |
| UX-015 | Gunakan bahasa antarmuka yang netral, singkat, dan tidak menghakimi murid. | Konteks BK memerlukan komunikasi yang aman dan profesional. | Microcopy diperiksa pada form, alert, empty state, dan error. | Disetujui ahli |
| UX-016 | Perlakukan wireframe sebagai kerangka UX content-first untuk informasi, pengelompokan, prioritas, layout dasar, navigasi, dan alur. Ahli UI menyempurnakan komponen serta visual akhir; perubahan struktur besar memerlukan alasan UX dan pencatatan. | Wireframe low-fidelity bukan desain visual final, tetapi tetap memuat keputusan UX awal. | Review menilai maksud UX, ketercakupan informasi, kemudahan penggunaan, daya tarik visual, responsif, dan aksesibilitas; bukan kemiripan gaya atau posisi secara piksel. | Disetujui ahli |
| UX-017 | Pisahkan low-fidelity sebagai kontrak UX dan high-fidelity sebagai kontrak visual. | Mengurangi tafsir visual agen dan menjaga keterlacakan perubahan. | Setiap halaman melewati lowfi-approved lalu hifi-approved sebelum frontend. | Disetujui pemilik proyek |
| UX-018 | Gunakan arah visual tenang, ramah, profesional, biru lembut, navy, putih hangat, krem, soft UI ringan, ikon outline, dan ilustrasi pendukung. | Sesuai karakter layanan BK tanpa terasa menghukum atau generik. | Nilai presisi berasal dari foundations high-fidelity dan design tokens. | Disetujui pemilik proyek |

## Aturan pencatatan keputusan UX baru

- Keputusan harus menjawab masalah penggunaan yang nyata.
- Cantumkan dampak implementasi dan halaman yang terdampak.
- Jangan memakai UX decision log untuk menetapkan hak akses atau kebijakan domain.
- Jika style guide kemudian mengubah tampilan, pertahankan prinsip interaksi kecuali ada alasan penggunaan yang lebih kuat.
