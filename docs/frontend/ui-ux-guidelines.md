# Pedoman UI/UX Frontend

## Prinsip

- Utamakan kejelasan kerja Guru BK, bukan dekorasi.
- Tampilkan informasi sesuai kebutuhan peran dan konteks tindakan.
- Gunakan pola yang konsisten sebelum membuat pola baru.
- Kurangi paparan data sensitif dan hindari bahasa yang menghakimi murid.
- Setiap keputusan visual harus tetap dapat digunakan dengan keyboard dan pada layar kecil.

## App shell dan navigasi

- Desktop memakai sidebar biru yang dapat dipindai dengan cepat.
- Menu aktif memakai latar krem, teks gelap, dan inset shadow yang tetap memiliki kontras memadai.
- Layar sempit memakai offcanvas/drawer Bootstrap; konten utama tidak berada di bawah sidebar.
- Top bar memuat judul konteks, breadcrumb bila diperlukan, notifikasi, dan akun.
- Hanya menu yang sah bagi peran yang ditampilkan.
- Jangan membuat navigasi berbeda tanpa alasan pada halaman satu modul.

## Hierarki halaman

Urutan umum:

1. judul dan penjelasan singkat;
2. status konteks seperti read-only atau waktu sinkronisasi;
3. aksi utama;
4. filter atau ringkasan;
5. konten inti;
6. informasi pendukung dan audit.

Batasi satu aksi utama pada satu konteks visual. Aksi berisiko tidak boleh diletakkan berdekatan dengan aksi rutin tanpa pemisahan.

## Form

- Gunakan satu kolom untuk form kompleks; dua kolom hanya untuk pasangan field pendek dan tetap kembali satu kolom pada layar sempit.
- Label selalu terlihat. Placeholder tidak menggantikan label.
- Tandai field wajib dengan teks atau simbol yang dijelaskan.
- Tampilkan bantuan sebelum pengguna melakukan kesalahan bila aturan tidak lazim.
- Validasi server tetap menjadi sumber akhir; UI menampilkan pesan yang spesifik dan dapat diperbaiki.
- Field kondisional muncul setelah pilihan pemicu tanpa menghilangkan data yang sudah diisi secara tidak sengaja.
- Data sangat sensitif dipisahkan dari ringkasan umum dan diberi penjelasan akses.

## Daftar dan tabel

- Sediakan pencarian, filter, jumlah hasil, status filter aktif, dan reset yang jelas.
- Gunakan header tabel yang tetap dapat dipahami tanpa tooltip.
- Pada mobile, prioritaskan identitas, status, pembaruan, dan aksi detail; pindahkan informasi sekunder ke detail atau kartu.
- Jangan membuat seluruh baris menjadi satu-satunya target klik tanpa indikasi fokus.
- Pagination, sorting, dan filter harus mempertahankan batas akses.

## Status dan feedback

- Gunakan teks bersama warna/ikon.
- Loading menggunakan skeleton atau spinner dengan label aksesibel sesuai durasi dan konteks.
- Empty state menjelaskan apakah data memang kosong, filter terlalu sempit, atau pengguna tidak memiliki kewenangan.
- Error menjelaskan tindakan pemulihan tanpa membocorkan objek sensitif.
- Success feedback singkat dan menyebut hasil tindakan.
- Akses ditolak tidak mengonfirmasi keberadaan objek yang tidak boleh diketahui.

## Responsif

- Mobile: prioritas pada satu tugas dan informasi inti.
- Tablet: layout dapat memakai dua area bila tidak mengurangi keterbacaan.
- Desktop: manfaatkan ruang untuk ringkasan dan detail tanpa memperpanjang baris teks.
- Hindari breakpoint khusus halaman bila grid Bootstrap sudah memadai.
- Uji zoom browser sampai 200 persen tanpa kehilangan fungsi.

## Aksesibilitas

- Gunakan elemen HTML semantik sebelum menambah ARIA.
- Semua kontrol memiliki nama aksesibel.
- Urutan fokus mengikuti urutan visual dan tugas.
- Fokus keyboard terlihat jelas.
- Modal memerangkap fokus dan mengembalikan fokus ke pemicu setelah ditutup.
- Pesan error terhubung ke field terkait.
- Jangan menaruh informasi penting hanya dalam warna, ikon, hover, atau tooltip.
- Pastikan target sentuh cukup besar dan tidak berhimpitan.

## Data sensitif

- Jangan menampilkan isi konsultasi sensitif pada dashboard, tabel umum, notifikasi, atau laporan umum.
- Jangan memasukkan data sensitif ke URL, atribut HTML, console log, analytics, atau pesan error.
- Gunakan data sintetis pada screenshot, test, dan Storybook/demo bila tersedia.
- Read-only berarti tidak ada kontrol perubahan dan tidak ada endpoint mutasi yang dipanggil.
