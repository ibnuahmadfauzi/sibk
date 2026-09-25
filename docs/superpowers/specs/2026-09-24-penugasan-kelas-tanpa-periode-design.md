# Penugasan Kelas Tanpa Periode

**Tanggal:** 24 September 2026
**Status:** Menunggu review pengguna
**Scope:** Penugasan Guru BK, state membership murid, owner kasus, dan snapshot kasus/konsultasi yang diperlukan oleh scope akses serta laporan.

## Keputusan dan sumber kebenaran

Pengguna memutuskan bahwa kontrak periode penugasan Guru BK dihapus sekarang, lalu memperbarui PRD, SRS, dan `docs/api-contract.md` secara manual. Ketiganya adalah kontrak aktif dan juga menetapkan state membership per tahun, owner kasus tanpa periode, serta snapshot tahun/kelas layanan. `SIBK_Final_Schema_Baseline_v1.0.md` adalah rancangan schema ke depan, bukan mandat untuk melakukan domain reset.

Satu kelas pada satu tahun ajaran mempunyai paling banyak satu row `teacher_assignments`. Koordinator dapat mengganti `user_id` pada row yang sama. Audit append-only menyimpan pelaku, waktu, dan nilai sebelum/sesudah pergantian. Penugasan tidak mempunyai tanggal efektif, tanggal akhir, nomor SK, catatan, atau soft delete. Kasus yang sudah dibuat tetap mempunyai owner pada `case_assignments`; pergantian pengampu kelas tidak memindahkan owner kasus.

Satu murid mempunyai paling banyak satu state membership pada satu tahun ajaran. Pergantian kelas memperbarui row yang sama dan mencatat audit sebelum/sesudah. Kasus mempunyai tepat satu row owner tetap tanpa periode, soft delete, atau assignment tambahan. Kasus dan konsultasi menyimpan `academic_year_id` serta `classroom_id` ketika dicatat; perubahan membership kemudian tidak mengubah snapshot itu.

Tahun ajaran masih memiliki kolom tanggal legacy dalam schema saat ini, tetapi penentuan pengampu, scope akses, dan konteks halaman ini tidak boleh bergantung pada tanggal itu. Consumer tahun ajaran lain dan domain e-Tatib/prestasi/proses keluar tetap pekerjaan terpisah.

## Alur halaman dan mutasi

`/assignments/classes` menjadi satu halaman. Koordinator BK dapat mengatur penugasan; Guru BK melihat penugasannya, sedangkan Waka dan Admin IT memperoleh ringkasan sesuai kewenangan pada SRS/API Contract. Daftar berbasis kelas aktif pada tahun konteks, termasuk kelas tanpa pengampu bagi Koordinator. Tabel menampilkan nama kelas, jumlah murid aktif, Guru BK, status `Ditugaskan`/`Belum Ditugaskan`, dan aksi yang membuka satu modal pemilihan Guru BK hanya bagi Koordinator. Filter hanya pencarian kelas dan status tersebut. Tahun konteks tampil sebagai informasi; aktivasi tahun ajaran tetap di halaman ini dan memakai service readiness yang ada.

Tahun konteks default ialah tahun aktif. Tahun persiapan ialah tahun nonaktif yang belum pernah diaktifkan (`activated_at` kosong) dan mempunyai kelas aktif. Bila tidak ada tahun aktif dan hanya satu tahun persiapan, halaman memakai tahun persiapan itu. Bila ada lebih dari satu kandidat persiapan, halaman yang sama menampilkan pilihan tahun dan Koordinator memilih konteksnya; backend tidak menebak. `academic_year_id` pada GET hanya boleh menunjuk tahun aktif atau tahun persiapan tersebut. Tahun riwayat dan pasangan kelas/tahun yang tidak sesuai ditolak.

POST `/assignments/classes` hanya menerima `classroom_id` dan `user_id`. Tahun diturunkan dari kelas pada server. Form Request dan service menolak Guru BK nonaktif/bukan role Guru BK, kelas nonaktif, tahun riwayat, serta field lama yang mencoba mengatur tahun atau periode. Service mengunci kelas dalam transaksi; insert pertama membuat row dan audit, pilihan guru yang sama mengembalikan row tanpa write/audit, pilihan guru lain memperbarui row dan mencatat audit sebelum/sesudah. Constraint UNIQUE (`classroom_id`, `academic_year_id`) menjadi pengaman konkurensi database. Aksi hanya untuk Koordinator BK di policy dan server.

Penugasan pada tahun persiapan tidak memberi scope murid sampai tahun diaktifkan. Sesudah aktivasi, scope Guru BK memakai penugasan kelas pada tahun aktif dan membership murid aktif pada tahun itu, tanpa tanggal periode. Guru BK lama tetap dapat membaca kasus miliknya sesuai policy owner, tetapi tidak otomatis memperoleh scope kelas setelah `user_id` diganti. Hak ubah/arsip kasus hanya milik owner kasus.

## Data existing dan migration

Migration forward-only mengubah `teacher_assignments` ke state tunggal: menghapus `effective_from`, `effective_until`, `decision_number`, `notes`, dan `deleted_at`; lalu membuat UNIQUE (`classroom_id`, `academic_year_id`) dan index akses guru. `student_class_memberships` mendapat UNIQUE (`student_id`, `academic_year_id`) setelah periode dihapus. `case_assignments` mendapat UNIQUE (`case_id`) setelah periode, soft delete, dan assignment tambahan legacy disingkirkan. Kasus/konsultasi memperoleh FK snapshot tahun ajaran dan kelas. Migration tidak dijalankan pada database shared/production oleh agent.

Sebelum menghapus kolom, migration memeriksa seluruh row termasuk soft-deleted. Duplikat pasangan kelas/tahun, duplikat murid/tahun, owner kasus ganda/tidak ada, assignment kasus tambahan, atau row yang soft-deleted menyebabkan migration gagal sebelum operasi schema. Data tersebut memerlukan keputusan pemetaan tersendiri; memilih state/owner secara otomatis berisiko mengubah hak akses. Pemeriksaan dilakukan dalam jendela tanpa penulisan domain terkait agar kegagalan tidak meninggalkan schema setengah berubah pada MySQL.

Snapshot kasus/konsultasi existing diisi sebelum periode membership dibuang. Pemetaan memakai membership yang berlaku pada tanggal layanan dan tahun ajaran asal yang tunggal. Rekam yang tidak mempunyai konteks atau mempunyai beberapa kandidat ditahan untuk peninjauan, bukan ditebak dari kelas saat ini. Snapshot kasus/layanan baru diisi saat create dalam transaksi yang sama. Snapshot lama tidak diperbarui saat murid pindah kelas, tahun berubah, atau Guru BK diganti.

Audit lama tetap append-only. Nilai periode dan SK dalam event lama tidak diubah. Tidak dibuat tabel histori penugasan baru; perubahan setelah migration dicatat oleh `AuditService` pada row yang sama. Seeder dan fixture test yang menulis field lama diperbarui.

## Consumer yang harus diselaraskan

- `Student::forActiveTeacherAssignment()` dan query akses kasus/konsultasi/profil memakai state assignment dan membership pada tahun aktif, tanpa syarat periode.
- Dashboard Guru BK/Koordinator, daftar murid, kesiapan aktivasi, serta proyeksi Waka memakai state yang sama. Readiness mensyaratkan tepat satu pengampu Guru BK aktif per kelas operasional.
- Laporan dan histori profil membaca snapshot kelas/tahun kasus/konsultasi, bukan mencocokkan membership saat ini atau tanggal layanan ke periode lama.
- `CaseAssignment` dan policy kasus memakai owner tetap; pemanggil `effectiveOn()` dan jalur `additional` legacy dihapus dari alur aktif.
- `TeacherAssignment` tidak lagi menyediakan scope/status `effectiveOn`, `scheduledOn`, `endedOn`, `statusOn`, atau `effectiveEnd`. Semua pemanggilnya dihapus atau diganti dengan query state yang sesuai.
- View form panjang `/assignments/classes/manage` dihapus. Route lama tetap berupa redirect ke halaman tunggal setelah validasi konteks.
- `case_assignments` tetap dipakai untuk owner awal dan otorisasi kasus; tidak ada pengalihan owner.

## Amendemen kontrak

- PRD/SRS/API Contract yang telah diubah pengguna tetap menjadi acuan. Implementasi diperiksa terhadap `ASN-01`–`ASN-03`, `MD-13`, `NFR-06`, `CASE-13`, dan kontrak snapshot laporan. Jika ada rincian interface yang belum tegas, keputusan dicatat pada plan sebelum kode ditulis.

## Verifikasi

Targeted test harus membuktikan create, no-op tanpa audit, update dengan audit, dua request bersaing, penolakan field/tahun palsu, scope tahun persiapan sebelum/sesudah aktivasi, update membership dengan audit, owner kasus yang tetap, snapshot layanan saat kelas berubah, serta migration disposable untuk data tunggal dan konflik duplikat/ambigu. Test otorisasi Koordinator/Guru BK/Waka/Admin tetap wajib. Tidak menjalankan full suite, build, atau migration pada database shared/production tanpa perintah pengguna.
