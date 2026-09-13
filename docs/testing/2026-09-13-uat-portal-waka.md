# UAT Portal Waka Berbasis Tujuan

## Identitas Pelaksanaan

- Rencana: `docs/superpowers/plans/2026-09-13-portal-waka-berbasis-tujuan.md`
- Tanggal penyelesaian: 14 September 2026
- Branch: `cobasidebar`
- SHA implementasi: `9524890573ddad8e649a170532ca38a18f779b4e`
- Browser: Google Chrome lokal melalui Browser Use/Browser Harness
- URL aplikasi: `http://127.0.0.1:8000`
- Rekaman Browser Use: tidak dibuat
- Screenshot lokal sementara: `storage/framework/testing/screenshots/` (diabaikan Git)

## Hasil Automated Verification

| Gate | Hasil | Bukti |
| --- | --- | --- |
| Focused feature tests | PASS | 55 test, 448 assertion, 0 failure |
| Full Laravel suite | PASS | 411 test, 3.148 assertion, 0 failure |
| Pint | PASS | Seluruh file PHP sesuai format |
| `config:cache` | PASS | Konfigurasi berhasil dikompilasi |
| `view:cache` | PASS | Seluruh Blade berhasil dikompilasi |
| Frontend checker | PASS | Seluruh halaman dan navigasi bersama lolos |
| Vite build | PASS | 75 modul ditransformasi |
| `git diff --check` | PASS | Tidak ada whitespace error |
| Cleanup cache | PASS | Cache konfigurasi dan view dibersihkan kembali |

Pada Windows, `npm.ps1` ditolak oleh execution policy. Gate frontend dijalankan
melalui executable ekuivalen `npm.cmd` dan lulus.

## Matriks Viewport

| Viewport | Dashboard | Murid dengan Kasus | Laporan | Navigasi | Overflow halaman |
| --- | --- | --- | --- | --- | --- |
| 1440 x 900 | PASS | Tabel desktop PASS | Tabel desktop dan tiga tab PASS | Sidebar tetap PASS | Tidak ada |
| 1024 x 768 | PASS | Tabel desktop PASS | Tabel dapat digulir di dalam panel PASS | Sidebar tetap PASS | Tidak ada |
| 768 x 1024 | PASS | Tabel desktop PASS | Tabel dapat digulir di dalam panel PASS | Offcanvas PASS | Tidak ada |
| 390 x 844 | PASS | Dua kartu murid PASS | Dua kartu penanganan dan tiga tab PASS | Offcanvas PASS | Tidak ada |

## Skenario Waka Kesiswaan

| Skenario | Hasil | Catatan |
| --- | --- | --- |
| Sidebar Waka murni | PASS | Hanya Dashboard, Murid dengan Kasus, Laporan, Notifikasi, dan Akun Saya |
| Dashboard berbasis tujuan | PASS | Empat metric, Membutuhkan Perhatian, Komposisi Status, dan Penanganan Terbaru tampil |
| Daftar murid teragregasi | PASS | Setiap identitas demo disajikan satu row/kartu dengan jumlah kasus dan kasus aktif per murid |
| Tab laporan dan deep link | PASS | `penanganan`, `rekap`, dan `laporan-akhir` mempertahankan active state melalui URL |
| Filter monitoring | PASS | Status `dibatalkan` menghasilkan empty state yang menjelaskan filter aktif |
| Reset filter | PASS | Tautan Reset filter mengembalikan daftar tanpa filter |
| Sort monitoring | PASS | Sort Murid naik menghasilkan Aisyah Rahmawati lalu Anggi Setiabudi dan `aria-sort="ascending"` |
| Pagination | PASS | Kontrak paginator dan parameter `page` lolos feature suite; kontrol halaman kedua tidak muncul pada UAT visual karena seed hanya berisi dua penanganan |
| Ekspor CSV | PASS | File 405 byte berisi header aman dan dua row; tidak memuat field terlarang |
| Rekap Periode | PASS | Hanya metric, distribusi, konteks kesiswaan, dan ringkasan kelas; nama murid tidak tampil |
| Laporan Akhir | PASS | Hanya menampilkan `Dalam pengembangan`; tidak ada Simpan Draf, Terbitkan, PDF, atau ekspor |
| Data nonterkoordinasi | PASS | Kolom Akses menampilkan `-` dan tidak menawarkan tautan detail |
| Mode hanya-baca | PASS | Tidak ditemukan tindakan Tambah, Ubah, Hapus, Simpan, atau Terbitkan |
| Responsive table/card | PASS | Tabel aktif mulai 768 px; kartu aktif di bawah 768 px |
| Urutan fokus | PASS | Fokus mengikuti urutan visual: skip link, menu, akun, tab, filter, Terapkan, ekspor, lalu ringkasan |
| Focus indicator | PASS | Link, field, dan tombol memiliki ring yang terlihat dan tidak tertutup header/sidebar |

## Skenario Koordinator BK

| Skenario | Hasil | Catatan |
| --- | --- | --- |
| Membuka Laporan Akhir | PASS | Placeholder `Dalam pengembangan` tampil |
| Navigasi pada halaman placeholder | PASS | Hanya tautan Laporan Akhir ditawarkan; tab Waka yang tidak sah disembunyikan |
| Monitoring Penanganan | PASS | Ditolak dengan halaman Akses Ditolak |
| Rekap Periode | PASS | Ditolak dengan halaman Akses Ditolak |
| Murid dengan Kasus | PASS | Ditolak dengan halaman Akses Ditolak |
| Ekspor monitoring | PASS | Ditolak dengan halaman Akses Ditolak |

## Audit dan Privasi

- Akun Waka menghasilkan 37 event `waka.monitoring.viewed` selama iterasi UAT
  dan satu event `waka.monitoring.exported`.
- Seluruh mode tercatat: `dashboard`, `students`, `reports.penanganan`,
  `reports.rekap`, dan `reports.laporan-akhir`.
- Akun Koordinator menghasilkan dua event pembacaan dengan mode
  `reports.laporan-akhir` saja.
- Ringkasan audit tidak memuat nama murid, NISN, kode kasus, `waka_summary`,
  `initial_info`, `internal_note`, `final_result`, atau `next_plan`.
- `before_values` dan `after_values` tetap kosong pada event portal Waka.
- Repository privacy scan pada view, service, dan controller Waka tidak
  menemukan akses field terlarang.

## Temuan dan Perbaikan UAT

1. Teks status sort yang menggunakan `visually-hidden` memperlebar halaman
   Monitoring Penanganan pada 1024 x 768. Status sort dipindahkan ke
   `aria-label`; regression test ditambahkan. Perbaikan: `f38ad85`.
2. Shadow fokus varian tombol menimpa ring fokus keyboard. Outline berbasis
   `--sibk-focus-ring` ditambahkan tanpa mengubah token desain. Perbaikan:
   `f38ad85`.
3. Koordinator masih melihat dua tab Waka yang berakhir 403. Tab kini dirender
   berdasarkan capability `viewWakaMonitoring`; regression test ditambahkan.
   Perbaikan: `9524890`.

Ketiga temuan diverifikasi ulang melalui browser dan automated gate. Tidak ada
temuan terbuka yang menghalangi penerimaan Portal Waka.
