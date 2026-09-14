# Matriks Otorisasi Umum Ruang BK

Baseline: SRS v1.1 `AUTH-01`–`AUTH-07` dan `GOV-01`. Enforcement wajib di server;
menu hanya membantu navigasi. Matriks ini memakai test reguler dengan fixture
lokal dan database test, tanpa dataset atau alat penelitian.

## Role dan batas data

| Area | Guru BK | Koordinator BK | Waka Kesiswaan | Admin IT |
| --- | --- | --- | --- | --- |
| Dashboard | Cakupan profesional aktif | Rekap sekolah | Proyeksi aman sekolah | Kesiapan teknis |
| Murid/kasus | Kelas pada periode efektif atau penugasan kasus khusus | Data umum sekolah | Proyeksi aman; detail hanya kasus terkoordinasi kepadanya | Ditolak |
| Konsultasi umum | Scope profesional aktif | Data umum sekolah | Ditolak | Ditolak |
| Isi konsultasi privat | Scope murid/kasus yang sah, termasuk histori | Hanya bila juga Guru BK dalam scope | Ditolak | Ditolak |
| Edit layanan | Owner/penulis asli dengan kewenangan aktif dan status yang mengizinkan | Role Koordinator tidak memberikan hak edit owner | Ditolak | Ditolak |
| Penugasan kelas/kasus | Ditolak | Diizinkan | Ditolak | Ditolak |
| Laporan BK dan CSV | Scope profesional/kasus khusus; identitas tersamarkan | Rekap gabungan; tanpa narasi sensitif | Ditolak | Ditolak |
| Portal Waka | Ditolak | Laporan Akhir placeholder saja | Monitoring/rekap aman; hanya-baca | Ditolak |
| Akun, master, integrasi | Ditolak | Ditolak | Ditolak | Diizinkan tanpa isi layanan BK |

Akun multi-role menggabungkan capability fungsi, tetapi fungsi Koordinator tidak
memperluas isi privat di luar scope Guru BK. Penugasan yang belum efektif atau
sudah berakhir tidak membuka daftar maupun URL detail. Histori dapat dibaca
penerus dalam scope, tetapi tidak otomatis dapat diedit.

`/consultations` mengarahkan ke `/cases?tab=konsultasi`; tujuan redirect memeriksa
otorisasi. Koreksi, notifikasi milik pengguna, dan riwayat masih mengikuti
baseline implementasi saat Checkpoint 3; pemensiunannya dilakukan pada Task 10
di Checkpoint 5. Matriks route di test tetap memeriksa fitur tersebut sampai
route dipensiunkan.

## Bukti test reguler

| Requirement / batas yang dijaga | Test |
| --- | --- |
| AUTH-01: tamu, sesi/akun nonaktif, login/logout | `AuthorizationMatrixTest`, `AuthenticationTest` |
| AUTH-02: daftar/detail, perpindahan kelas, awal/akhir penugasan, rollover | `StudentProfileTest`, `AssignmentManagementTest::test_direct_student_and_case_urls_follow_assignment_effective_dates`, `CaseManagementTest` |
| AUTH-03: policy objek, owner, follow-up bukan anak kasus menghasilkan 404 dan tidak berubah | `CaseManagementTest::test_follow_up_direct_urls_reject_a_child_from_another_case`, `ConsultationManagementTest`, `AchievementManagementTest` |
| AUTH-04/07: histori privat penerus, redaksi empat role, Koordinator+Guru dalam/luar scope, mutasi histori ditolak | `ConsultationManagementTest::test_successor_reads_old_private_history_but_cannot_edit_and_roles_are_redacted`, `test_coordinator_teacher_reads_private_history_only_in_professional_scope`, `test_temporary_identity_history_follows_active_special_case_assignment` |
| AUTH-05: proyeksi aman seluruh sekolah, detail terkoordinasi, Waka lain ditolak, mutasi tidak tersimpan | `WakaDashboardTest`, `WakaMonitoringTest`, `WakaReportPageTest`, `CaseManagementTest::test_object_policy_redacts_internal_notes_and_waka_access_is_audited`, `test_waka_cannot_create_coordination_even_for_a_coordinated_case` |
| AUTH-06/GOV-01: hak teknis dan capability operasional terpisah | `AuthorizationMatrixTest`, `AccountManagementTest`, `AssignmentManagementTest`, test integrasi/master |
| AUTH-02/03: HTML dan CSV rekap layanan memakai scope sama, termasuk kasus khusus; marker privat/identitas penuh tidak tampil | `ReportManagementTest::test_service_recap_html_and_csv_share_scope_and_exclude_private_narratives`, `test_csv_uses_same_redacted_rows_and_rejects_unavailable_formats`, `test_special_case_assignment_and_multi_role_are_evaluated_separately` |
| Audit mutasi tidak memuat narasi privat | `CaseManagementTest::test_teacher_creates_scoped_case_with_etatib_link_and_audit`, `ConsultationManagementTest::test_teacher_creates_consultation_with_physically_separated_private_note_and_safe_audit` |
| Notifikasi hanya milik penerima, selama fitur masih aktif | `DashboardNotificationTest::test_notifications_are_owner_scoped_and_read_actions_are_persisted` |

Assertion katalog, jumlah aktor penelitian, versi dataset, keselarasan manual/CSV,
reset fixture, serta command penelitian dipensiunkan bersama perangkatnya.
Assertion produk yang penting diperiksa lewat test di atas.

## Verifikasi

```powershell
php artisan test tests/Feature/AuthorizationMatrixTest.php tests/Feature/AuthenticationTest.php tests/Feature/StudentProfileTest.php tests/Feature/AssignmentManagementTest.php tests/Feature/CaseManagementTest.php tests/Feature/ConsultationManagementTest.php tests/Feature/ReportManagementTest.php tests/Feature/WakaDashboardTest.php tests/Feature/WakaMonitoringTest.php tests/Feature/WakaReportPageTest.php tests/Feature/DashboardNotificationTest.php
composer test
```

Gate umum repository tetap wajib. Perubahan laporan pada Checkpoint 4 dan
pensiun fitur pada Checkpoint 5 harus memperbarui matriks ini dan test terkait.
