# Implementation plan: tabel Penugasan Kelas berbasis Guru BK

## Keputusan dan batas scope

Acuan perilaku: PRD v1.1, SRS v1.1 (ASN-01–03, AUTH-01–07, MD-08–09), dan `docs/api-contract.md` bagian Penugasan Kelas. Plan ini mengganti penyajian halaman, bukan model data. Tidak ada spec baru.

- Tabel berkolom **No, Nama Guru, Kelas, Jumlah Murid, Status**. Satu baris mewakili satu Guru BK pada tahun ajaran terpilih, termasuk guru tanpa kelas.
- Kolom Kelas berisi chip nama kelas dan tombol **+** hanya bagi Koordinator BK. Tombol membuka dropdown/popover kelas aktif yang belum memiliki penugasan pada tahun tersebut. Pilihan langsung disimpan melalui `POST /assignments/classes` dengan `user_id` dan `classroom_id`.
- Nama pada chip tidak memindahkan Guru BK. Tombol **×** pada chip meminta konfirmasi, lalu membatalkan penugasan kelas melalui `DELETE /assignments/classes/{classroom}`. Kelas kembali menjadi unassigned dan tersedia pada dropdown **+**. Kemampuan pindah melalui service/POST yang sudah ada tidak dihapus karena masih tercantum dalam kontrak aktif, tetapi tidak diekspos oleh rancangan UI ini.
- Jumlah Murid = jumlah murid aktif pada seluruh kelas aktif yang sedang diampu guru dalam tahun terpilih. Status **Ditugaskan** bila ada minimal satu kelas, selain itu **Belum ditugaskan**.
- Guru BK hanya melihat barisnya sendiri. Waka Kesiswaan dan Admin IT hanya membaca ringkasan. Koordinator BK mengelola penugasan tahun aktif atau Persiapan; tahun arsip hanya dibaca.
- Filter `status` berubah makna menjadi status **guru**. `search_kelas` mencari nama kelas yang sedang diampu. Saat filter pencarian terisi, guru tanpa kelas tidak cocok; saat kosong, mereka tetap tampil.

## Analisis implementasi sekarang

`AssignmentController@index` saat ini mengambil `Classroom` sebagai baris, menghitung murid per kelas, dan memfilter kelas. Blade `resources/views/pages/assignments/classes/index.blade.php` memakai modal dan kolom Aksi. Bundle Vite lokal yang ditemukan masih versi sebelum impor modal, sehingga tombol sebelumnya dapat terlihat tetapi tidak merespons. Tabel baru cukup memakai data kelas dan penugasan yang sudah ada; tidak perlu tabel, migration, service, atau dependency baru.

`AssignmentService::assignClass()` sudah mengunci kelas, tahun ajaran, guru, dan state penugasan; unique index `teacher_assignments(classroom_id, academic_year_id)` menjaga satu pengampu per kelas/tahun. Form Request serta policy sudah membatasi POST kepada Koordinator. Pembatalan belum ada dan perlu aksi service serta otorisasi server tersendiri. Readiness aktivasi tahun ajaran dan scope murid mengikuti state penugasan, sehingga tambah dan batal harus memakai relasi yang sama tanpa mengubah owner kasus atau histori layanan.

## Dampak, risiko, dan penanganan

| Dampak atau risiko | Penanganan dalam implementasi |
| --- | --- |
| Kelas yang belum ditugaskan tidak lagi menjadi baris tabel. | Tampilkan hanya dalam dropdown **+** Koordinator; kesiapan aktivasi yang sudah ada tetap menunjukkan kelas yang belum siap. Guru tanpa kelas tetap memperoleh baris dengan status **Belum ditugaskan**. |
| Jumlah murid salah karena menghitung membership lama, tidak aktif, atau dari tahun lain. | Batasi kelas dan membership ke tahun terpilih, kelas aktif, membership aktif, dan murid aktif; jumlahkan sekali per kelas memakai `withCount`/relasi yang sudah ada. Test dua kelas dengan jumlah berbeda dan perpindahan tahun. |
| Dropdown dua Koordinator sama-sama melihat kelas kosong; kiriman kedua dapat memindahkan kelas tanpa disengaja karena POST lama mendukung perubahan guru. | Tambahkan precondition khusus form **+**: saat transaksi mengunci kelas, tolak jika kelas sudah mempunyai pengampu; tampilkan pesan bahwa daftar perlu dimuat ulang. Jalur POST lama tanpa precondition tetap memakai perilaku existing. Periksa apakah penanda precondition perlu dicatat pada API Contract sebelum implementasi; jangan mengubah PRD/SRS untuk detail UI ini. |
| Pembatalan salah kelas/guru atau double submit menghapus penugasan baru. | DELETE menerima kelas dan guru yang tampil saat konfirmasi; service mengunci state dan menolak bila pengampunya sudah berubah atau penugasan sudah tidak ada. Audit mencatat `before: guru_bk=<guru>` dan `after: guru_bk=null` dalam transaksi yang sama. |
| Pembatalan kelas pada tahun aktif langsung mengurangi scope Guru BK dan dapat membuat kesiapan tahun tidak lengkap. | Konfirmasi menyebut dampaknya; hitung ulang scope dan readiness dari state terkini. Owner kasus serta catatan lama tetap utuh dan mengikuti policy histori yang ada. |
| Kontrol terselip pada ringkasan Waka/Admin atau halaman Guru BK. | Render **+** dan **×** hanya bila `canManage`, lalu verifikasi POST/DELETE langsung tetap ditolak policy/Form Request bagi peran lain. |
| Dropdown terpotong oleh `table-responsive` atau bergantung pada bundle JS lama. | Gunakan elemen native `<details>` dengan daftar dalam alur tabel/sel sehingga baris dapat memanjang; periksa keyboard, fokus, layar sempit, dan penempatan tombol. Hindari modal serta dependency baru. |
| Filter `status=unassigned` dahulu berarti kelas kosong, sekarang berarti guru tanpa kelas. | Sesuaikan test dan teks filter. Pastikan dropdown **+** tetap menampilkan kelas kosong pada tahun terpilih meskipun tabel guru sedang difilter. |
| Tahun arsip atau belum ada tahun ajaran/guru/kelas. | Pertahankan pemilihan tahun dan aturan `canManage`; tampilkan empty state tanpa form rusak. Tahun Persiapan tetap belum memberi scope murid. |
| Seeder dummy yang sedang dikerjakan di worktree dapat berbenturan dengan test lama. | Pertahankan perubahan seeder yang belum dikomit; ubah hanya test daftar penugasan yang terdampak, lalu jalankan test seeder terarah bersama test penugasan. |

## Langkah pengembangan

### 1. Siapkan representasi data tabel

- Ubah `AssignmentController@index`: ambil Guru BK aktif sesuai scope peran; ambil kelas aktif pada tahun terpilih beserta satu penugasan dan `student_count` yang sudah difilter. Kelompokkan kelas menurut `user_id`, hitung jumlah kelas dan total murid, serta siapkan daftar kelas tanpa penugasan untuk dropdown.
- Terapkan filter status pada **guru**, pencarian pada nama kelas yang diampu, dan urutan stabil menurut nama guru. Gunakan koleksi/relasi yang tersedia; jangan menambah service query khusus bila controller tetap mudah dibaca.
- Pertahankan `GET /assignments/classes/manage` dan redirect aktivasi yang sudah ada agar tautan lama tetap berjalan.

### 2. Ganti tabel dan interaksi

- Ubah `resources/views/pages/assignments/classes/index.blade.php` menjadi lima kolom yang disetujui. Render nama kelas pada chip sebagai teks dan tombol **×** sebagai form DELETE bagi Koordinator; jumlah murid dan status berasal dari agregat langkah 1.
- Hapus modal, tombol gear/Aksi, dan listener modal khusus penugasan di `resources/js/app-dashboard.js` bila tidak dipakai lagi. Tombol **+** membuka daftar kelas kosong dengan `<details>`. Setiap pilihan adalah form POST kecil dengan CSRF, `user_id`, dan `classroom_id`; pengiriman langsung menyimpan lalu kembali ke tahun terpilih.
- Minta konfirmasi sebelum form **×** dikirim. Gunakan label tombol yang menyebut kelas dan guru bagi pembaca layar, target sentuh cukup besar, dan teks saat tidak ada kelas tersedia. Pastikan dropdown tetap terlihat di tabel responsif.

### 3. Lindungi aksi tambah dan tambahkan pembatalan

- Tambahkan pemeriksaan atomik untuk form **+** pada transaksi `AssignmentService::assignClass()` agar pilihan yang sudah diambil orang lain ditolak tanpa memindahkan pengampu. Pertahankan perilaku POST existing tanpa precondition, audit before/after, dan unique constraint.
- Tambahkan `DELETE /assignments/classes/{classroom}` di `routes/web.php`, controller tipis, dan `AssignmentService::unassignClass()`. Batasi ke Koordinator aktif dan tahun aktif/Persiapan; tolak arsip, kelas tanpa penugasan, dan pengampu yang sudah berubah sejak halaman dimuat. Hapus row state penugasan dalam transaksi setelah mencatat audit before/after. Redirect ke tahun terpilih dengan pesan hasil.
- Selaraskan hanya bagian Penugasan pada PRD, SRS, dan API Contract: Koordinator dapat mengubah state `assigned → unassigned`, konfirmasi UI, endpoint DELETE, audit `guru_bk=<guru> → null`, serta dampak langsung pada scope tahun aktif. Tidak perlu migration. Bila precondition form **+** menjadi bagian request publik, catat juga pada bagian POST API Contract.

### 4. Verifikasi dan handoff

- Sesuaikan `tests/Feature/AssignmentManagementTest.php` untuk baris guru tanpa kelas, agregat dua kelas, filter status guru, kelas kosong di dropdown, aksi tambah satu klik, konfirmasi/tombol batal, pengurangan jumlah murid, kelas kembali ke pilihan **+**, audit pembatalan, request stale, arsip, owner kasus tetap, dan pembatasan akses. Jalankan test otorisasi terarah yang sudah ada; jangan membuat suite baru yang menduplikasi implementasi.
- Jalankan `php artisan test --filter=AssignmentManagement`, test seeder terarah bila data dummy dipakai, lint PHP/Pint pada file yang diubah, dan `git diff --check`. Review tampilan manual di desktop serta viewport sempit. Build frontend dan full gate hanya bila pengguna menjalankannya sesuai kebijakan terminal repository.
- Setelah hasil lolos, perbarui handoff/checkpoint yang tersedia dan catat ringkasan di `docs/development-log.md` bila file itu ada. Commit perubahan fitur secara terpisah dari perubahan seeder yang sudah berjalan; tidak push tanpa instruksi.

## Kriteria selesai

Koordinator dapat menambahkan kelas kosong dari dropdown **+** dan membatalkan penugasannya melalui **×** setelah konfirmasi. Chip, total murid, status guru, kesiapan aktivasi, dan pilihan kelas kosong mengikuti state terbaru. DELETE yang tidak sah atau memakai tampilan lama ditolak tanpa mengubah penugasan. Pengguna selain Koordinator tidak melihat kontrol dan POST/DELETE langsung ditolak. Tidak ada UI pemindahan guru atau perubahan struktur database.
