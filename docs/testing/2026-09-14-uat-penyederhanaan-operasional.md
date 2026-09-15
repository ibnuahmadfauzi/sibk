# UAT Penyederhanaan Operasional Ruang BK

Status: **PENDING MANUAL**
Branch: `checkpoint-5b-operasional`
SHA aplikasi yang diuji CLI: `90f83d6`
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
| Nama/inisial tester | PENDING |
| Tanggal UAT | PENDING |
| Browser dan versi | PENDING |
| Branch/SHA | `checkpoint-5b-operasional` / `90f83d6` |
| Database engine | PENDING |
| Jumlah tabel | 36 pada fresh SQLite dan MySQL disposable |

## Checklist manual

Isi setiap sel dengan `PASS` atau `FAIL: <catatan singkat>`.

| No. | Skenario | 1440 × 900 | 768 × 1024 | 390 × 844 |
|---:|---|---|---|---|
| 1 | Guru BK melihat laporan dan data hanya dalam scope. | PENDING | PENDING | PENDING |
| 2 | Koordinator melihat rekap seluruh kelas tanpa isi konsultasi sensitif. | PENDING | PENDING | PENDING |
| 3 | Koreksi Data, Notifikasi, dan Riwayat Perubahan tidak tampil. | PENDING | PENDING | PENDING |
| 4 | Edit data belum selesai langsung membuka form. | PENDING | PENDING | PENDING |
| 5 | Edit data selesai meminta konfirmasi dan alasan. | PENDING | PENDING | PENDING |
| 6 | Hapus kasus/konsultasi meminta konfirmasi lalu mengarsipkan. | PENDING | PENDING | PENDING |
| 7 | Role selain owner gagal edit/arsip melalui URL langsung. | PENDING | PENDING | PENDING |
| 8 | Guru BK mencatat proses keluar tanpa menonaktifkan murid. | PENDING | PENDING | PENDING |
| 9 | Keputusan Batal mempertahankan murid aktif. | PENDING | PENDING | PENDING |
| 10 | Resmi keluar menolak layanan baru sejak tanggal efektif. | PENDING | PENDING | PENDING |
| 11 | Waka melihat semua status proses keluar tanpa aksi mutasi. | PENDING | PENDING | PENDING |
| 12 | Sinkronisasi roster tidak mengubah keputusan keluar. | PENDING | PENDING | PENDING |
| 13 | Admin IT membuat/reset akun dan melihat password sementara satu kali. | PENDING | PENDING | PENDING |
| 14 | Akun ber-password sementara hanya dapat membuka Ganti Password/Logout. | PENDING | PENDING | PENDING |
| 15 | Password kedaluwarsa ditolak dan sesi lama terputus. | PENDING | PENDING | PENDING |
| 16 | Dashboard role-aware tidak menampilkan aktivitas audit. | PENDING | PENDING | PENDING |
| 17 | Tidak ada overflow horizontal; focus state terlihat. | PENDING | PENDING | PENDING |

## Evidence otomatis

| Gate | Hasil |
|---|---|
| Focused feature gate | PASS — 100 test, 876 assertion |
| Full test | PASS — 444 test, 3.463 assertion |
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

## Hasil akhir

Gate otomatis lulus. Checkpoint 5B belum boleh ditutup atau digabungkan sampai
tester manusia mengisi seluruh skenario dan mengembalikan hasil `PASS`.
