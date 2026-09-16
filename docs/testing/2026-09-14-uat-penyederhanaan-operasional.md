# UAT Penyederhanaan Operasional Ruang BK

Status: **PENDING MANUAL — UJI ULANG TERBATAS**
Branch: `checkpoint-5b-operasional`
SHA aplikasi yang diuji CLI: `e72ea04`
Tanggal gate CLI: 16 September 2026

UAT wajib dilakukan manusia melalui browser biasa. Jangan memakai browser
automation, headless browser, CUA, atau perbandingan screenshot otomatis.

## Prasyarat

- Gunakan database lokal/disposable, bukan shared atau production.
- Siapkan akun aktif Guru BK, Koordinator BK, Waka Kesiswaan, dan Admin IT.
- Siapkan murid dalam/luar scope, kasus dan konsultasi aktif/selesai, satu data
  arsip, serta proses keluar berstatus `dalam_proses`, `batal`, dan
  `resmi_keluar`.
- Siapkan akun uji dengan password sementara valid dan satu yang kedaluwarsa.
- URL awal: `/dashboard`, `/reports`, `/cases`, `/students`,
  `/waka/student-departures`, dan `/admin/users`.

## Identitas pelaksana

| Isian | Hasil |
|---|---|
| Nama/inisial tester | QA pengguna (hasil dilaporkan oleh pengguna) |
| Tanggal UAT | 16 September 2026 |
| Browser dan versi | Tidak dilaporkan |
| Branch/SHA | `checkpoint-5b-operasional` / `1c54867` |
| Database engine | SQLite disposable |
| Jumlah tabel | 36 pada fresh SQLite dan MySQL disposable |

## Checklist manual

Isi setiap sel dengan `PASS` atau `FAIL: <catatan singkat>`.

| No. | Skenario | 1440 × 900 | 768 × 1024 | 390 × 844 |
|---:|---|---|---|---|
| 1 | Guru BK melihat laporan dan data hanya dalam scope. | PASS | PASS | PASS |
| 2 | Koordinator melihat rekap seluruh kelas tanpa isi konsultasi sensitif. | PASS | PASS | PASS |
| 3 | Koreksi Data, Notifikasi, dan Riwayat Perubahan tidak tampil. | PASS | PASS | PASS |
| 4 | Edit data belum selesai langsung membuka form. | PASS | PASS | PASS |
| 5 | Edit data selesai meminta konfirmasi dan alasan. | PASS | PASS | PASS |
| 6 | Hapus kasus/konsultasi meminta konfirmasi lalu mengarsipkan. | PASS | PASS | PASS |
| 7 | Role selain owner gagal edit/arsip melalui URL langsung. | PASS | PASS | PASS |
| 8 | Guru BK mencatat proses keluar tanpa menonaktifkan murid. | PASS | PASS | PASS |
| 9 | Keputusan Batal mempertahankan murid aktif. | PASS | PASS | PASS |
| 10 | Resmi keluar menolak layanan baru sejak tanggal efektif. | PASS | PASS | PASS |
| 11 | Waka melihat semua status proses keluar tanpa aksi mutasi. | PASS | PASS | PASS |
| 12 | Sinkronisasi roster tidak mengubah keputusan keluar. | PASS | PASS | PASS |
| 13 | Admin IT membuat/reset akun dan melihat password sementara satu kali. | PASS | PASS | PASS |
| 14 | Akun ber-password sementara hanya dapat membuka Ganti Password/Logout. | PASS | PASS | PASS |
| 15 | Password kedaluwarsa ditolak dan sesi lama terputus. | PASS | PASS | PASS |
| 16 | Dashboard role-aware tidak menampilkan aktivitas audit. | PASS | PASS | PASS |
| 17 | Tidak ada overflow horizontal; focus state terlihat. | PASS | PASS | PASS |

## Evidence otomatis

Pra-UAT berbantuan alat pada 16 September 2026 menemukan bahwa form pembuatan
kasus belum merender pilihan wajib `service_field_id`. Temuan diperbaiki pada
commit `1c54867` dan dikunci oleh test regresi. Eksplorasi ini bukan UAT manual
dan tidak mengubah sel checklist di atas.

| Gate | Hasil |
|---|---|
| Focused feature gate pasca-perbaikan UAT | PASS - 110 test, 795 assertion |
| Focused feature gate pasca-review | PASS - 126 test, 975 assertion; MySQL 3 test, 7 assertion |
| Full test | PASS - 454 test, 3.498 assertion |
| Pint | PASS |
| Composer strict dan audit | PASS — tidak ada advisory |
| NPM audit | PASS — 0 vulnerability tingkat tinggi |
| Config, route, dan view cache | PASS; cache sudah dibersihkan |
| Frontend checker | PASS |
| Build Vite | PASS |
| `git diff --check` | PASS |
| Fresh SQLite | PASS — 36 tabel; invariant, lifecycle password, dan queue `sync` tersedia |
| Fresh/incremental MySQL disposable | PASS — 36 tabel; constraint dan konversi arsip lulus; database sudah dihapus |
| Scan fitur retired | PASS — kecocokan hanya assertion negatif pada test/checker |
| Scan field sensitif laporan/Waka | PASS — tidak ada kecocokan |
| Scan credential | PASS — hanya istilah/kebijakan generik dan fixture sintetis; tidak ada nilai nyata |
| Tindak lanjut review | PASS — password wajib berbeda, snapshot audit lengkap, dan batas tanggal keluar konsisten |

## Uji ulang pasca-review

Review final menemukan celah expiry sesi aktif, race pembuatan kasus dengan
keputusan keluar resmi, dan otorisasi stale pada proses keluar. Perbaikan
`e72ea04` telah lulus test regresi dan gate penuh, tetapi dibuat setelah UAT
manual awal. QA perlu mengulang hanya skenario terdampak berikut pada aplikasi
SHA tersebut serta melaporkan browser dan versinya.

| Skenario | 1440 × 900 | 768 × 1024 | 390 × 844 |
|---|---|---|---|
| 8 — Guru BK mencatat proses keluar | PENDING | PENDING | PENDING |
| 9 — Koordinator menetapkan Batal | PENDING | PENDING | PENDING |
| 10 — Resmi keluar menolak layanan baru | PENDING | PENDING | PENDING |
| 15 — Password sementara kedaluwarsa ditolak | PENDING | PENDING | PENDING |

## Hasil akhir

Gate otomatis lulus. QA pengguna sebelumnya melaporkan seluruh 17 skenario
pada tiga viewport `PASS` untuk aplikasi SHA `1c54867`. Checkpoint 5B belum
dapat ditutup sampai uji ulang terbatas pada SHA `e72ea04` berstatus `PASS`
dan metadata browser/versi dilaporkan.
