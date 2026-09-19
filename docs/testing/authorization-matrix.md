# Matriks Otorisasi Umum Ruang BK

Baseline: SRS v1.1 `AUTH-01`–`AUTH-07` dan `GOV-01`. Enforcement wajib di server;
menu hanya membantu navigasi. Matriks ini memakai test reguler dengan fixture
lokal dan database test, tanpa dataset atau alat penelitian.

## Role dan batas data

| Area | Guru BK | Koordinator BK | Waka Kesiswaan | Admin IT |
| --- | --- | --- | --- | --- |
| Dashboard | Cakupan profesional aktif | Rekap sekolah | Proyeksi aman sekolah | Kesiapan teknis |
| Murid/kasus | Kelas pada periode efektif atau penugasan kasus khusus | Data umum sekolah | Proyeksi detail kasus hanya-baca | Ditolak |
| Konsultasi | Scope profesional aktif | Data umum sekolah | Proyeksi detail konsultasi hanya-baca | Ditolak |
| Narasi konsultasi | Scope murid/kasus yang sah, termasuk histori | Hanya bila juga Guru BK dalam scope | Proyeksi enam field layanan yang disetujui | Ditolak |
| Edit/arsip layanan | Owner/penulis asli dengan kewenangan aktif | Role Koordinator tidak memberikan hak edit/arsip owner | Ditolak | Ditolak |
| Penugasan kelas/kasus | Ditolak | Diizinkan | Ditolak | Ditolak |
| Laporan BK dan CSV | Scope profesional/kasus khusus; identitas tersamarkan | Rekap gabungan; tanpa narasi sensitif | Ditolak | Ditolak |
| Portal Waka | Ditolak | Laporan Akhir placeholder saja | Monitoring/rekap aman; hanya-baca | Ditolak |
| Akun, master, integrasi | Ditolak | Ditolak | Ditolak | Diizinkan tanpa isi layanan BK |

Akun multi-role menggabungkan capability fungsi, tetapi fungsi Koordinator tidak
memperluas isi privat di luar scope Guru BK. Penugasan yang belum efektif atau
sudah berakhir tidak membuka daftar maupun URL detail. Histori dapat dibaca
penerus dalam scope, tetapi tidak otomatis dapat diedit.

`/consultations` mengarahkan ke `/cases?tab=konsultasi`; tujuan redirect memeriksa
otorisasi. Route koreksi, notifikasi, riwayat audit, dan bookmark pratinjaunya
telah dipensiunkan pada Checkpoint 5A dan diuji mengembalikan 404.

## Bukti test reguler

| Requirement / batas yang dijaga | Test |
| --- | --- |
| AUTH-01: tamu, sesi/akun nonaktif, login/logout | `AuthorizationMatrixTest`, `AuthenticationTest` |
| AUTH-02: daftar/detail, perpindahan kelas, awal/akhir penugasan, rollover | `StudentProfileTest`, `AssignmentManagementTest::test_direct_student_and_case_urls_follow_assignment_effective_dates`, `CaseManagementTest` |
| AUTH-03: policy objek, owner, follow-up bukan anak kasus menghasilkan 404 dan tidak berubah | `CaseManagementTest::test_follow_up_direct_urls_reject_a_child_from_another_case`, `ConsultationManagementTest`, `AchievementManagementTest` |
| AUTH-04/07: histori privat penerus, redaksi empat role, Koordinator+Guru dalam/luar scope, mutasi histori ditolak | `ConsultationManagementTest::test_successor_reads_old_private_history_but_cannot_edit_and_roles_are_redacted`, `test_coordinator_teacher_reads_private_history_only_in_professional_scope`, `test_temporary_identity_history_follows_active_special_case_assignment` |
| AUTH-04/05: Waka aktif membaca seluruh detail kasus/konsultasi lewat proyeksi allowlist, semua mutasi ditolak | `WakaDashboardTest`, `WakaMonitoringTest`, `AuthorizationMatrixTest` pada Task 6 |
| AUTH-06/GOV-01: hak teknis dan capability operasional terpisah | `AuthorizationMatrixTest`, `AccountManagementTest`, `AssignmentManagementTest`, test integrasi/master |
| AUTH-02/03: HTML dan CSV rekap layanan memakai scope sama, termasuk kasus khusus; marker privat/identitas penuh tidak tampil | `ReportManagementTest::test_service_recap_html_and_csv_share_scope_and_exclude_private_narratives`, `test_csv_uses_same_redacted_rows_and_rejects_unavailable_formats`, `test_special_case_assignment_and_multi_role_are_evaluated_separately` |
| Audit mutasi tidak memuat narasi privat | `CaseManagementTest::test_teacher_creates_scoped_case_with_etatib_link_and_audit`, `ConsultationManagementTest::test_teacher_creates_consultation_with_physically_separated_private_note_and_safe_audit` |
| Route fitur retired tidak terdaftar | `AuthorizationMatrixTest::test_retired_feature_routes_are_not_registered` |
| Dashboard tidak menampilkan narasi audit | `DashboardTest::test_dashboard_uses_role_context_instead_of_audit_activity_feed` |

Assertion katalog, jumlah aktor penelitian, versi dataset, keselarasan manual/CSV,
reset fixture, serta command penelitian dipensiunkan bersama perangkatnya.
Assertion produk yang penting diperiksa lewat test di atas.

## Verifikasi

```powershell
php artisan test tests/Feature/AuthorizationMatrixTest.php tests/Feature/AuthenticationTest.php tests/Feature/StudentProfileTest.php tests/Feature/AssignmentManagementTest.php tests/Feature/CaseManagementTest.php tests/Feature/ConsultationManagementTest.php tests/Feature/ReportManagementTest.php tests/Feature/WakaDashboardTest.php tests/Feature/WakaMonitoringTest.php tests/Feature/WakaReportPageTest.php tests/Feature/DashboardTest.php
composer test
```

Gate umum repository tetap wajib. Perubahan laporan pada Checkpoint 4 dan
pensiun fitur pada Checkpoint 5A harus memperbarui matriks ini dan test terkait.
