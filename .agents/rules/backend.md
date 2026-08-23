# Ruang BK — Backend Development Rules

Status: **Backend Aktif Dikembangkan**

## Source of Truth
1. `docs/requirements/SRS_Aplikasi_BK_v1.0.md` untuk aturan bisnis, perilaku fungsional, dan matriks hak akses.
2. `docs/requirements/PRD_Aplikasi_BK_v1.0.md` untuk ruang lingkup dan batasan produk.
3. `docs/requirements-index.md` sebagai indeks selektif.

---

## Standar Kode (PHP 8.3 & Laravel 13)

- **Strict Typing:** Selalu sertakan `declare(strict_types=1);` di baris pertama setiap file PHP.
- **Modern PHP Features:** Gunakan native typing PHP 8.3, constructor property promotion, readonly properties/classes, match expressions, nullsafe operators (`?->`), dan typed returns.
- **Pemisahan Layer (Thin Controller):**
  - **Controllers:** Hanya menangani ekstraksi HTTP request, pemanggilan Service/Action, dan return response/view.
  - **Validation:** Wajib menggunakan Form Request classes (`app/Http/Requests`). Jangan melakukan validasi langsung di Controller.
  - **Services / Actions:** Seluruh logika bisnis, transaksi database (`DB::transaction`), dan mutasi data ditempatkan di `app/Services`.
  - **Models & Scopes:** Eloquent Models bersih dengan relasi eksplisit, casting tipe, dan Query Scopes untuk isolasi data (`accessibleBy`, `activeForYear`, dll.).

---

## Aturan Arsitektur & Hak Akses (Otorisasi)

### 1. Multi-Role & Akun Rangkap (AUTH-07, ACC-01)
- Mendukung pengguna dengan banyak peran via tabel `roles` dan pivot `user_roles`.
- Akun dengan peran rangkap (**Koordinator BK** sekaligus **Guru BK**) menerapkan hak fungsi secara terpisah:
  - Kapabilitas Koordinator mencakup penugasan rombel/kasus, verifikasi koreksi, dan melihat rekap gabungan.
  - Akses catatan konseling sensitif hanya terbuka untuk murid dalam kelas binaan atau penugasan kasus khususnya.
  - **Peran Koordinator tidak otomatis membuka isi catatan konsultasi sensitif di luar scope kelas binaannya.**

### 2. Matriks Otorisasi (AUTH-01 s.d. AUTH-06)
- **Guru BK:** Scope data terbatas pada kelas binaan di tahun ajaran aktif serta kasus khusus yang ditugaskan. Dapat membaca histori murid binaan sampai lulus, tetapi tidak dapat mengubah catatan lama.
- **Waka Kesiswaan:** Hanya memiliki akses *read-only* pada ringkasan umum dan detail kasus yang secara eksplisit tercatat dikoordinasikan kepadanya via `case_coordinations`. Isi lengkap konsultasi dan catatan internal sensitif dikecualikan (AUTH-05, CONS-02).
- **Admin IT:** Mengelola akun, infrastruktur, data master, dan rekonsiliasi data tanpa hak membuka isi catatan layanan/kasus sensitif (AUTH-06, ACC-02).

### 3. Identitas Sementara & Rekonsiliasi (MD-03, MD-04)
- Identitas sementara dikelola pada tabel mandiri `temporary_students` (bukan sekadar flag di tabel murid).
- Menampung: `nisn`, `name`, `created_by`, status rekonsiliasi (`menunggu_rekonsiliasi`, `terekonsiliasi`, `ditahan_konflik`), dan deteksi konflik.
- Rekonsiliasi oleh Admin IT menghubungkan kasus ke data master Dapodik tanpa menduplikasi data murid.

### 4. Data Referensi Dinamis (REF-01, NFR-07)
- Nilai status, bidang layanan, jenis tindak lanjut, dan kategori menggunakan Foreign Key ke tabel `references` (tidak di-hardcode sebagai enum di tabel transaksional).

### 5. Jejak Audit Append-Only (AUD-01, NFR-03, NFR-08)
- Tabel `audit_logs` **tidak memiliki soft delete** dan **tidak memiliki endpoint/method update atau delete**.
- Model `AuditLog` diatur *immutable* di level aplikasi (menolak update dan delete).
- Pencatatan dilakukan otomatis via Model Observers / Custom Events saat terjadi mutasi data penting.

### 6. Retensi & Soft Deletes (NFR-08)
- Seluruh tabel transaksional (`cases`, `case_assignments`, `case_coordinations`, `follow_ups`, `consultations`, `achievements`, `data_corrections`, `teacher_assignments`, `temporary_students`) menyertakan `$table->softDeletes()` untuk memenuhi retensi minimum 3 tahun.

### 7. Penanganan Tanggal (CASE-10)
- Sistem wajib membedakan dan menyimpan secara terpisah: `waktu pencatatan` (created_at), `tanggal layanan`, `tanggal rencana`, dan `tanggal pelaksanaan`.

---

## Integrasi Eksternal
- **Dapodik:** Sumber master data murid, kelas, keanggotaan kelas, dan tahun ajaran.
- **e-Tatib:** Sumber eksternal data pelanggaran dan poin (read-only mirror via `external_tatib_records`). Tidak ada write-back ke e-Tatib pada MVP.
- Anomali atau kegagalan pemetaan ditandai dengan status `ditahan_konflik` untuk diperiksa oleh Admin IT.
