# Penugasan Kelas Tanpa Periode

**Tanggal:** 24 September 2026  
**Status:** Menunggu review pengguna  
**Scope:** Penugasan Guru BK per kelas dan tahun ajaran, halaman `/assignments/classes`, serta consumer langsungnya.

## Keputusan dan sumber kebenaran

Pengguna memutuskan bahwa kontrak periode penugasan Guru BK dihapus sekarang. PRD, SRS, dan `docs/api-contract.md` akan diamendemen bersama implementasi dan menjadi kontrak aktif. `SIBK_Final_Schema_Baseline_v1.0.md` adalah rancangan schema ke depan, bukan mandat untuk melakukan domain reset.

Satu kelas pada satu tahun ajaran mempunyai paling banyak satu row `teacher_assignments`. Koordinator dapat mengganti `user_id` pada row yang sama. Audit append-only menyimpan pelaku, waktu, dan nilai sebelum/sesudah pergantian. Penugasan tidak mempunyai tanggal efektif, tanggal akhir, nomor SK, catatan, atau soft delete. Kasus yang sudah dibuat tetap mempunyai owner pada `case_assignments`; pergantian pengampu kelas tidak memindahkan owner kasus.

Periode keanggotaan kelas murid, kasus, dan domain lain tidak diubah oleh pekerjaan ini. Tahun ajaran masih memiliki kolom tanggal legacy dalam schema saat ini, tetapi penentuan pengampu dan hak akses tidak boleh bergantung pada tanggal itu. Penghapusan kolom tanggal tahun ajaran dilakukan saat domain master akademik disentuh secara khusus.

## Alur halaman dan mutasi

`/assignments/classes` menjadi satu halaman Koordinator BK. Daftar berbasis kelas aktif pada tahun konteks, termasuk kelas tanpa pengampu. Tabel menampilkan nama kelas, jumlah murid aktif, Guru BK, status `Ditugaskan`/`Belum Ditugaskan`, dan aksi yang membuka satu modal pemilihan Guru BK. Filter hanya pencarian kelas dan status tersebut. Tahun konteks tampil sebagai informasi; aktivasi tahun ajaran tetap di halaman ini dan memakai service readiness yang ada.

Tahun konteks default ialah tahun aktif. Tahun persiapan ialah tahun nonaktif yang belum pernah diaktifkan (`activated_at` kosong) dan mempunyai kelas aktif. Bila tidak ada tahun aktif dan hanya satu tahun persiapan, halaman memakai tahun persiapan itu. Bila ada lebih dari satu kandidat persiapan, halaman yang sama menampilkan pilihan tahun dan Koordinator memilih konteksnya; backend tidak menebak. `academic_year_id` pada GET hanya boleh menunjuk tahun aktif atau tahun persiapan tersebut. Tahun riwayat dan pasangan kelas/tahun yang tidak sesuai ditolak.

POST `/assignments/classes` hanya menerima `classroom_id` dan `user_id`. Tahun diturunkan dari kelas pada server. Form Request dan service menolak Guru BK nonaktif/bukan role Guru BK, kelas nonaktif, tahun riwayat, serta field lama yang mencoba mengatur tahun atau periode. Service mengunci kelas dalam transaksi; insert pertama membuat row dan audit, pilihan guru yang sama mengembalikan row tanpa write/audit, pilihan guru lain memperbarui row dan mencatat audit sebelum/sesudah. Constraint UNIQUE (`classroom_id`, `academic_year_id`) menjadi pengaman konkurensi database. Aksi hanya untuk Koordinator BK di policy dan server.

Penugasan pada tahun persiapan tidak memberi scope murid sampai tahun diaktifkan. Sesudah aktivasi, scope Guru BK memakai penugasan kelas pada tahun aktif dan keanggotaan murid aktif. Keanggotaan murid masih memakai aturan periode sendiri sampai domain itu diperbarui. Guru BK lama tetap dapat membaca kasus miliknya sesuai policy owner, tetapi tidak otomatis memperoleh scope kelas setelah `user_id` diganti.

## Data existing dan migration

Migration forward-only mengubah `teacher_assignments` ke state tunggal: menghapus `effective_from`, `effective_until`, `decision_number`, `notes`, dan `deleted_at`; lalu membuat UNIQUE (`classroom_id`, `academic_year_id`) dan index akses guru. Migration tidak dijalankan pada database shared/production oleh agent.

Sebelum menghapus kolom, migration memeriksa seluruh row termasuk soft-deleted. Jika ada lebih dari satu row untuk pasangan kelas/tahun atau ada row soft-deleted, migration gagal dengan pesan yang menyebut pasangan bermasalah dan tidak menghapus data. Data tersebut memerlukan keputusan pemetaan tersendiri; memilih owner secara otomatis dari periode lama berisiko mengubah hak akses. Pemeriksaan dilakukan sebelum operasi schema dan dalam jendela tanpa penulisan penugasan agar kegagalan tidak meninggalkan schema setengah berubah pada MySQL.

Audit lama tetap append-only. Nilai periode dan SK dalam event lama tidak diubah. Tidak dibuat tabel histori penugasan baru; perubahan setelah migration dicatat oleh `AuditService` pada row yang sama. Seeder dan fixture test yang menulis field lama diperbarui.

## Consumer yang harus diselaraskan

- `Student::forActiveTeacherAssignment()` dan query akses kasus/konsultasi/profil hanya menggunakan assignment pada tahun aktif, tanpa syarat tanggal penugasan.
- Dashboard Guru BK/Koordinator, daftar murid, kesiapan aktivasi, serta proyeksi Waka memakai state penugasan yang sama. Readiness mensyaratkan tepat satu pengampu Guru BK aktif per kelas operasional.
- `TeacherAssignment` tidak lagi menyediakan scope/status `effectiveOn`, `scheduledOn`, `endedOn`, `statusOn`, atau `effectiveEnd`. Semua pemanggilnya dihapus atau diganti dengan query state yang sesuai.
- View form panjang `/assignments/classes/manage` dihapus. Route lama tetap berupa redirect ke halaman tunggal setelah validasi konteks.
- `case_assignments` tetap dipakai untuk owner awal dan otorisasi kasus; domain owner tidak diubah.

## Amendemen kontrak

- PRD: cakupan Penugasan menyebut satu pengampu per kelas/tahun, perubahan resmi, dan audit; rujukan periode serta dasar keputusan dihapus.
- SRS: `ASN-01`–`ASN-03` dan `NFR-06` menyatakan state tunggal dan histori audit, tanpa periode efektif. Kriteria aktivasi serta scope setelah tahun aktif diselaraskan.
- API Contract: GET dan POST Penugasan Kelas, request dua field, otorisasi Koordinator, serta create/no-op/update dan error trust boundary. Aturan bisnis dirujuk melalui ID SRS.

## Verifikasi

Targeted test harus membuktikan create, no-op tanpa audit, update dengan audit, dua request bersaing, penolakan field/tahun palsu, scope tahun persiapan sebelum/sesudah aktivasi, owner kasus yang tetap, serta migration disposable untuk data tunggal dan konflik duplikat. Test otorisasi Koordinator/Guru BK/Waka/Admin tetap wajib. Tidak menjalankan full suite, build, atau migration pada database shared/production tanpa perintah pengguna.
