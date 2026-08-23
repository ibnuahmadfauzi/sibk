# Panduan Pengujian Manual RBAC Ruang BK

Dokumen ini adalah petunjuk eksekusi untuk `RBAC-001` sampai `RBAC-041`. Pengujian membandingkan:

`Role/Capability/Resource → Expected Access → Actual Access → Pass/Fail`

Istilah permission pada penelitian ini berarti capability yang diterapkan melalui Gate/Policy. Batas data diterapkan melalui scope kelas, periode penugasan, penugasan kasus, kepemilikan resource, dan koordinasi Waka. Aplikasi tidak menggunakan tabel permission terpisah.

## 1. Persiapan dan Aturan Penting

Isi password pengujian di `.env`:

```env
SIBK_SEED_ACCOUNT_PASSWORD=password-pilihan-anda
```

Password minimal delapan karakter dan tidak boleh dimasukkan ke artikel, CSV, catatan, atau screenshot. Jalankan:

```text
php artisan optimize:clear
php artisan migrate
php artisan rbac:scenario-reset
php artisan rbac:scenario-verify
```

`rbac:scenario-reset` menampilkan versi dataset, tanggal baseline, dan lokasi CSV baru. Gunakan hanya CSV paling baru. Primary key dan URL pada CSV lama dapat tidak lagi berlaku setelah reset.

Jalankan aplikasi melalui Laragon atau `php artisan serve`, lalu siapkan browser biasa dan incognito. Setiap berganti aktor, logout atau gunakan jendela incognito terpisah agar sesi tidak tercampur.

### Cara membaca dan mengisi CSV

- `Resource Label` menjelaskan data nyata di balik URL.
- `Preconditions` menjelaskan alasan resource seharusnya boleh atau ditolak.
- `Request/Route` berisi metode dan URL aktual. Untuk `GET /cases/12`, ketik hanya `/cases/12`.
- `Must Appear` adalah teks yang wajib terlihat bila halaman diizinkan.
- `Must Not Appear` adalah teks yang dilarang terlihat.
- Isi `Actual Access` dengan `Allow`, `Allow terbatas`, `Deny 403`, `Deny 404`, atau `Redirect`.
- Isi `Pass` hanya bila status, resource, dan redaksi data semuanya sesuai expected.

Nama evidence:

```text
{Scenario-ID}_{Actor}_{Actual}.png
```

Contoh: `RBAC-010_guru-a_Deny-403.png`. Untuk redirect, screenshot halaman tujuan. Untuk skenario automated companion, screenshot hasil terminal dan tulis nama method test pada kolom `Notes`.

### Arti hasil

| Expected | Kriteria Pass |
|---|---|
| `Allow` atau `Allow penuh` | Halaman/resource yang tepat tampil beserta marker wajib. |
| `Allow terbatas/redacted` | Halaman tampil, tetapi field/marker terlarang tidak tampil. |
| `Deny 403` | Pengguna sudah login dan server menolak kewenangan. |
| `Deny 404` | Nested resource yang tidak cocok disembunyikan sebagai tidak ditemukan. |
| `Allow + redirect` | Endpoint mengarahkan ke resource tujuan yang tepat. |

Jika expected Deny tetapi halaman data tampil, hasilnya tetap `Fail` walaupun tombol ubah tidak tersedia. Menyembunyikan tombol bukan pengganti enforcement akses baca pada server.

## 2. Akun Pengujian

Semua akun memakai `SIBK_SEED_ACCOUNT_PASSWORD` yang sama.

| Aktor CSV | Email | Peran |
|---|---|---|
| `guru_a` | `rbac.guru.a@ruangbk.test` | Guru BK pembanding utama |
| `guru_b` | `rbac.guru.b@ruangbk.test` | Guru BK pemilik data pembanding |
| `koordinator` | `rbac.koordinator@ruangbk.test` | Koordinator BK |
| `waka_a` | `rbac.waka.a@ruangbk.test` | Waka penerima koordinasi |
| `waka_b` | `rbac.waka.b@ruangbk.test` | Waka pembanding |
| `admin` | `rbac.admin@ruangbk.test` | Admin IT |
| `multi_role` | `rbac.multi@ruangbk.test` | Guru BK dan Koordinator BK |
| `guru_inactive` | `rbac.guru.nonaktif@ruangbk.test` | Guru BK nonaktif |

`guest`, `credential_salah`, dan `guru_a_dinonaktifkan_saat_sesi_aktif` adalah kondisi pengujian, bukan akun tambahan.

## 3. Baseline Data yang Harus Cocok

Jangan mulai Paket 2 bila `php artisan rbac:scenario-verify` gagal.

### Kelas, murid, dan periode

| Murid | Kelas baseline | Kewenangan penting |
|---|---|---|
| RBAC Murid Alpha | RBAC XII-A | Guru A aktif melalui penugasan kelas. |
| RBAC Murid Beta | RBAC XII-B | Guru B aktif; Guru A hanya mendapat akses profesional melalui penugasan khusus `K-RBAC-003`. |
| RBAC Murid Gamma | Pindah dari XII-A ke XII-M | Scope lama Guru A berakhir sehari sebelum baseline; akun multi-role aktif mulai tanggal baseline. |
| RBAC Murid Delta | RBAC XII-B | Guru B aktif; penugasan kelas Guru A baru mulai 90 hari setelah baseline. |

Pada `RBAC XII-B`, Guru B berlaku sampai hari ke-89 dan Guru A mulai hari ke-90. Yang berada di masa depan adalah periode penugasan Guru A, bukan murid atau tanggal kasus.

### Resource transaksi

| Kode | Pemilik/relasi baseline |
|---|---|
| `K-RBAC-001` | Kasus Alpha, pemilik Guru A. |
| `K-RBAC-002` | Kasus Beta, pemilik Guru B, dikoordinasikan hanya kepada Waka A. |
| `K-RBAC-003` | Kasus Beta, pemilik Guru B, penugasan tambahan aktif kepada Guru A. |
| `K-RBAC-004` | Kasus Alpha milik Guru A yang sudah selesai. |
| `K-RBAC-005` | Kasus Delta milik Guru B; Guru A baru mendapat kelasnya pada masa depan. |
| `KNS-RBAC-001` | Konsultasi Alpha oleh Guru A, marker `RBAC-PRIVATE-A`. |
| `KNS-RBAC-002` | Konsultasi Beta oleh Guru B, marker `RBAC-PRIVATE-B`. |
| `KNS-RBAC-003` | Histori konsultasi Gamma oleh Guru A, marker `RBAC-PRIVATE-HISTORY`. |
| Prestasi Terverifikasi | Milik Beta dan dapat dibaca Waka A secara read-only. |
| Prestasi Menunggu | Milik Beta tetapi belum boleh dibaca Waka. |

## 4. Paket 1 — AUTH-01 Autentikasi dan Status Akun

#### RBAC-001 — Guest membuka dashboard

- Aktor: guest; pastikan seluruh sesi logout.
- Langkah: buka URL pada CSV, yaitu `/dashboard`.
- Expected: redirect ke `/login`; form login tampil.
- Catat: `Actual Access = Redirect/Deny`, `Pass` bila tidak ada data dashboard yang tampil.

#### RBAC-002 — Guru A aktif membuka dashboard

- Aktor: login sebagai `guru_a`.
- Langkah: buka `/dashboard`.
- Expected: HTTP 200 dan dashboard tampil.
- Catat: `Actual Access = Allow`.

#### RBAC-003 — Credential salah

- Aktor: logout; gunakan email Guru A dan password yang sengaja salah.
- Langkah: kirim form login.
- Expected: kembali ke login dengan pesan `Email atau kata sandi tidak sesuai`.
- Catat: `Actual Access = Deny`; jangan gunakan email akun nonaktif pada skenario ini.

#### RBAC-004 — Akun nonaktif mencoba login

- Aktor: `guru_inactive`.
- Langkah: login memakai password dataset yang benar.
- Expected: pesan generik `Email atau kata sandi tidak sesuai`; teks yang mengungkap bahwa akun terdaftar/nonaktif tidak boleh tampil.
- Catat: `Actual Access = Deny`. Judul `Periksa kembali isian Anda` hanya pembungkus validasi.

#### RBAC-005 — Akun dinonaktifkan saat sesi masih aktif

- Prasyarat: jalankan reset agar Guru A aktif.
- Langkah: login Guru A di browser pertama; login Admin di incognito; buka `/admin/users`; nonaktifkan `RBAC Guru BK A`; kembali ke browser pertama dan refresh `/dashboard`.
- Expected: sesi Guru A diakhiri, redirect ke login, dan pesan `Akun tidak aktif. Hubungi Admin IT sekolah.` tampil.
- Catat: `Actual Access = Deny/Redirect`. Membuka dashboard sebagai guest bukan pengganti skenario ini.
- Setelah evidence: jalankan reset dan gunakan CSV baru sebelum Paket 2.

## 5. Paket 2 — AUTH-02 Scope Guru BK

Login sebagai `guru_a`. Pastikan verifier lulus dan gunakan URL dari CSV hasil reset terakhir.

#### RBAC-006 — Daftar murid terscope

- Langkah: buka `/students` tanpa filter pencarian.
- Expected: `RBAC Murid Alpha` dan `RBAC Murid Beta` tampil; `RBAC Murid Gamma` dan `RBAC Murid Delta` tidak tampil.
- Alasan: Beta masuk scope profesional Guru A melalui penugasan aktif pada `K-RBAC-003`, bukan melalui kelas.
- Catat: `Actual Access = Allow terbatas`; screenshot harus memperlihatkan isi daftar, bukan hanya menu.

#### RBAC-007 — Profil Alpha dalam scope kelas

- Langkah: buka URL profil `RBAC Murid Alpha` dari CSV.
- Expected: HTTP 200 dan nama Alpha tampil.
- Catat: `Actual Access = Allow`.

#### RBAC-008 — Profil Delta pada kelas penugasan masa depan

- Langkah: buka URL profil `RBAC Murid Delta` dari CSV.
- Expected: HTTP 403.
- Alasan: Guru A baru ditugaskan ke XII-B 90 hari setelah baseline dan Delta tidak memiliki penugasan kasus khusus kepada Guru A.
- Catat: `Actual Access = Deny 403`.

#### RBAC-009 — Kasus milik Guru A

- Langkah: buka URL `K-RBAC-001`.
- Expected: HTTP 200 dan nomor `K-RBAC-001` tampil.
- Catat: `Actual Access = Allow`.

#### RBAC-010 — Kasus Guru B tanpa penugasan khusus

- Langkah: buka URL `K-RBAC-002`.
- Expected: HTTP 403.
- Alasan: akses profesional Guru A terhadap Beta berasal dari `K-RBAC-003` dan tidak membuka semua kasus Beta. Akses khusus tetap per kasus.
- Catat: `Actual Access = Deny 403`. Jika detail tampil, hasil `Fail` meskipun aksi ubah dibatasi.

#### RBAC-011 — Kasus dengan penugasan tambahan Guru A

- Langkah: buka URL `K-RBAC-003`.
- Expected: HTTP 200 dan nomor `K-RBAC-003` tampil.
- Catat: `Actual Access = Allow`.

#### RBAC-012 — Kasus pada kelas penugasan Guru A yang belum berlaku

- Langkah: buka URL `K-RBAC-005`.
- Expected: HTTP 403.
- Alasan: kasusnya bukan “kasus masa depan”; penugasan kelas Guru A yang belum efektif. Tanggal absolut mulai penugasan tersedia pada kolom `Preconditions` CSV.
- Catat: `Actual Access = Deny 403`. Jika halaman tampil normal, reset dataset, gunakan CSV baru, dan ulangi; bila tetap tampil, catat `Fail`.

#### RBAC-013 — Laporan rekap layanan terscope

- Langkah: buka URL laporan pada CSV.
- Expected wajib tampil: `K-RBAC-001`, `K-RBAC-003`, `K-RBAC-004`, `KNS-RBAC-001`, dan `KNS-RBAC-002`.
- Expected tidak boleh tampil: `K-RBAC-002`, `K-RBAC-005`, `KNS-RBAC-003`, teks `RBAC-PRIVATE`, atau `RBAC-INTERNAL`.
- Alasan: laporan mengikuti scope profesional saat ini, tetapi akses kasus khusus tetap per kasus; konsultasi umum Beta dapat terbaca karena Beta berada dalam scope profesional melalui `K-RBAC-003`.
- Catat: `Actual Access = Allow terbatas`; satu kebocoran marker terlarang berarti `Fail`.

## 6. Paket 3 — AUTH-03 Pemeriksaan per Objek

#### RBAC-014 — Nested tindak lanjut tidak cocok dengan kasus URL

- Aktor: `guru_a`.
- Prasyarat: URL menggabungkan `K-RBAC-001` dengan ID tindak lanjut milik `K-RBAC-002`.
- Langkah: buka URL persis dari CSV; jangan memperbaiki pasangan ID.
- Expected: HTTP 404 dan isi tindak lanjut tidak tampil.
- Catat: `Actual Access = Deny 404`; 404 adalah Pass karena mencegah IDOR nested resource.

#### RBAC-015 — Notifikasi milik Guru A

- Aktor: `guru_a`.
- Langkah: buka URL notifikasi pada CSV.
- Expected: redirect ke detail `K-RBAC-003`, bukan sekadar kembali ke daftar notifikasi.
- Catat: `Actual Access = Allow + redirect`; screenshot halaman kasus tujuan.

#### RBAC-016 — Notifikasi milik Guru B

- Aktor: tetap `guru_a`.
- Langkah: buka URL notifikasi pembanding pada CSV.
- Expected: HTTP 403 dan pesan notifikasi Guru B tidak tampil.
- Catat: `Actual Access = Deny 403`.

#### RBAC-017 — Waka mencoba membuat koordinasi

- Aktor: `waka_a`; channel `Automated companion`.
- Langkah: jalankan `php artisan test --filter=AuthorizationResearchScenarioTest`.
- Expected: POST koordinasi menghasilkan 403 dan jumlah record koordinasi tidak berubah.
- Catat: hasil test terminal sebagai evidence. Address bar tidak dapat menguji POST ini secara sah.

## 7. Paket 4 — AUTH-04 dan AUTH-07 Privasi Konsultasi

#### RBAC-018 — Guru A membaca konsultasi privat yang sah

- Aktor: `guru_a`.
- Langkah: buka `KNS-RBAC-001` melalui URL CSV.
- Expected: nomor konsultasi dan marker `RBAC-PRIVATE-A` tampil.
- Catat: `Actual Access = Allow penuh`.

#### RBAC-019 — Koordinator membaca metadata tanpa catatan privat

- Aktor: `koordinator`.
- Langkah: buka resource yang sama, `KNS-RBAC-001`.
- Expected: nomor dan `RBAC ringkasan umum Alpha` tampil; `RBAC-PRIVATE-A` tidak tampil.
- Catat: `Actual Access = Allow terbatas/redacted`, bukan Allow penuh.

#### RBAC-020 — Waka membuka konsultasi dari kasus terkoordinasi

- Aktor: `waka_a`.
- Langkah: buka `KNS-RBAC-002`.
- Expected: HTTP 403.
- Alasan: koordinasi `K-RBAC-002` hanya membuka detail kasus; tidak membuka konsultasi terkait.
- Catat: `Actual Access = Deny 403`.

#### RBAC-021 — Admin membuka konsultasi

- Aktor: `admin`.
- Langkah: buka `KNS-RBAC-001`.
- Expected: HTTP 403.
- Catat: `Actual Access = Deny 403`.

#### RBAC-022 — Multi-role membaca histori privat murid scope baru

- Aktor: `multi_role`.
- Langkah: buka `KNS-RBAC-003`.
- Expected: HTTP 200 dan `RBAC-PRIVATE-HISTORY` tampil.
- Alasan: Gamma kini berada pada XII-M yang ditugaskan kepada fungsi Guru BK akun multi-role.
- Catat: `Actual Access = Allow penuh/read-only`.

#### RBAC-023 — Multi-role mengubah konsultasi Guru sebelumnya

- Aktor: tetap `multi_role`.
- Langkah: buka URL edit `KNS-RBAC-003` dari CSV.
- Expected: HTTP 403 karena pencatat konsultasi lama adalah Guru A.
- Catat: `Actual Access = Deny 403`.

#### RBAC-024 — Fungsi Koordinator tidak membuka catatan privat luar scope

- Aktor: `multi_role`.
- Langkah: buka `KNS-RBAC-001`.
- Expected: metadata dan `RBAC ringkasan umum Alpha` tampil melalui fungsi Koordinator; `RBAC-PRIVATE-A` tidak tampil karena Alpha di luar scope Guru BK akun multi-role.
- Catat: `Actual Access = Allow terbatas/redacted`.

## 8. Paket 5 — AUTH-05 Waka Read-only

#### RBAC-025 — Waka A membaca kasus terkoordinasi

- Aktor: `waka_a`.
- Langkah: buka `K-RBAC-002`.
- Expected: HTTP 200 dan nomor kasus tampil; `RBAC-INTERNAL-CASE-B` tidak tampil; tidak tersedia aksi perubahan kasus.
- Catat: `Actual Access = Allow terbatas/read-only`.

#### RBAC-026 — Waka A membuka kasus tanpa koordinasi

- Aktor: tetap `waka_a`.
- Langkah: buka `K-RBAC-001`.
- Expected: HTTP 403.
- Catat: `Actual Access = Deny 403`.

#### RBAC-027 — Waka B membuka koordinasi Waka A

- Aktor: `waka_b`.
- Langkah: buka `K-RBAC-002`.
- Expected: HTTP 403 karena koordinasi ditujukan kepada Waka A.
- Catat: `Actual Access = Deny 403`.

#### RBAC-028 — Waka A membuka form penyelesaian kasus

- Aktor: `waka_a`.
- Langkah: buka URL `/resolve` untuk `K-RBAC-002` dari CSV.
- Expected: HTTP 403 walaupun detail kasus boleh dibaca.
- Catat: `Actual Access = Deny 403`.

#### RBAC-029 — Waka A membuka laporan konsultasi

- Aktor: `waka_a`.
- Langkah: buka URL laporan konsultasi pada CSV.
- Expected: HTTP 403.
- Catat: `Actual Access = Deny 403`.

#### RBAC-030 — Waka A membaca prestasi terverifikasi

- Aktor: `waka_a`.
- Langkah: buka URL `RBAC-Prestasi-Terverifikasi`.
- Expected: HTTP 200 dan nama kegiatan tampil secara read-only.
- Alasan: prestasi terverifikasi milik Beta, sedangkan kasus Beta dikoordinasikan kepada Waka A.
- Catat: `Actual Access = Allow terbatas/read-only`.

#### RBAC-031 — Waka A membaca prestasi menunggu

- Aktor: tetap `waka_a`.
- Langkah: buka URL `RBAC-Prestasi-Menunggu`.
- Expected: HTTP 403.
- Catat: `Actual Access = Deny 403`.

## 9. Paket 6 — AUTH-06 Pemisahan Admin IT

Login sebagai `admin`.

#### RBAC-032 — Admin mengelola akun

- Langkah: buka `/admin/users`.
- Expected: HTTP 200 dan GUI `Kelola Akun` tampil, bukan respons JSON mentah.
- Catat: `Actual Access = Allow`.

#### RBAC-033 — Admin membuka Data Master

- Langkah: buka `/data-master`.
- Expected: HTTP 200 dan halaman Data Master tampil.
- Catat: `Actual Access = Allow`.

#### RBAC-034 — Admin membuka daftar kasus

- Langkah: buka `/cases` langsung.
- Expected: HTTP 403.
- Catat: `Actual Access = Deny 403`.

#### RBAC-035 — Admin membuka profil layanan murid

- Langkah: buka URL profil Alpha dari CSV.
- Expected: HTTP 403; hak Data Master tidak membuka profil layanan BK.
- Catat: `Actual Access = Deny 403`.

#### RBAC-036 — Admin membuka laporan layanan

- Langkah: buka `/reports`.
- Expected: HTTP 403.
- Catat: `Actual Access = Deny 403`.

#### RBAC-037 — Admin membuka prestasi

- Langkah: buka URL prestasi terverifikasi dari CSV.
- Expected: HTTP 403.
- Catat: `Actual Access = Deny 403`.

## 10. Paket 7 — AUTH-07 Multi-role dan Fungsi Koordinator

#### RBAC-038 — Multi-role mengelola penugasan kasus

- Aktor: `multi_role`.
- Langkah: buka `/assignments/cases`.
- Expected: HTTP 200; halaman penugasan kasus tampil melalui fungsi Koordinator.
- Catat: `Actual Access = Allow sebagai Koordinator`.

#### RBAC-039 — Multi-role membaca Gamma melalui fungsi Guru BK

- Aktor: tetap `multi_role`.
- Langkah: buka URL profil Gamma dari CSV.
- Expected: HTTP 200 dan `RBAC Murid Gamma` tampil.
- Catat: `Actual Access = Allow sebagai Guru BK`.

#### RBAC-040 — Guru A mengelola penugasan kelas

- Aktor: `guru_a`.
- Langkah: buka `/assignments/classes/manage` langsung.
- Expected: HTTP 403 karena role Guru BK tidak mewarisi fungsi Koordinator.
- Catat: `Actual Access = Deny 403`.

#### RBAC-041 — Koordinator mengelola penugasan kelas

- Aktor: `koordinator`.
- Langkah: buka `/assignments/classes/manage`.
- Expected: HTTP 200 dan halaman `Kelola Penugasan` tampil.
- Catat: `Actual Access = Allow`.

## 11. Automated Companion dan Pengujian Ulang

Jalankan:

```text
php artisan test --filter=AuthorizationResearchScenarioTest
```

Automated companion memeriksa baseline, seluruh hubungan resource, status HTTP, redaksi marker privat, laporan/CSV, nested binding, redirect notifikasi, mutasi negatif, audit, dan idempotensi reset. Evidence otomatis mendampingi pengujian manual; kolom Actual pada CSV tetap diisi dari eksekusi manual kecuali skenario berchannel `Automated companion`.

Reset dataset sebelum mengulang kelompok yang mengubah status atau kepemilikan. Setelah reset:

1. Buang referensi URL lama, bukan file evidencenya.
2. Gunakan CSV yang baru dibuat.
3. Jalankan `php artisan rbac:scenario-verify`.
4. Catat versi dataset dan tanggal baseline pada lampiran hasil.

## 12. Rekap dan Batas Klaim

Rekap per requirement atau role:

| Requirement/Role | Jumlah skenario | Pass | Fail | Persentase kesesuaian |
|---|---:|---:|---:|---:|
| AUTH-01 atau nama role | n | p | f | `(p / n) × 100%` |

Kegagalan tidak boleh dihapus. Laporkan expected, actual, resource, titik enforcement, dampak terbatas pada skenario tersebut, perbaikan, dan hasil pengujian ulang.

Hasil penelitian hanya menunjukkan enforcement pada role, capability, resource, endpoint, dan skenario yang diuji. Hasil ini tidak membuktikan keamanan sistem secara keseluruhan.
