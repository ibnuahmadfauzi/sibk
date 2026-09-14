# UAT Laporan Guru BK dan Koordinator

Status: **PENDING MANUAL**  
Branch: `checkpoint-4-laporan`  
SHA aplikasi yang diuji CLI: `af9e69a`  
Tanggal gate CLI: 15 September 2026

UAT wajib dilakukan manusia melalui browser biasa. Jangan memakai browser
automation, headless browser, CUA, atau perbandingan screenshot otomatis.

## Prasyarat

- Gunakan database lokal/disposable, bukan shared atau production.
- Siapkan satu akun aktif Guru BK dengan murid dalam dan luar scope.
- Siapkan satu akun aktif Koordinator BK dengan data dari lebih dari satu Guru BK.
- Sertakan kasus, konsultasi, tindak lanjut, pelanggaran, prestasi, serta satu
  identitas sementara yang belum direkonsiliasi.
- URL awal: `/reports`, `/reports?tab=pelanggaran`,
  `/reports?tab=layanan`, dan `/reports?tab=prestasi`.

## Identitas pelaksana

| Isian | Hasil |
|---|---|
| Nama/inisial tester | PENDING |
| Tanggal UAT | PENDING |
| Browser dan versi | PENDING |
| Branch/SHA | `checkpoint-4-laporan` / `af9e69a` |

## Checklist manual

Isi setiap sel dengan `PASS` atau `FAIL: <catatan singkat>`.

| No. | Skenario | 1440 × 900 | 768 × 1024 | 390 × 844 |
|---:|---|---|---|---|
| 1 | Guru BK hanya melihat murid dalam scope. | PENDING | PENDING | PENDING |
| 2 | Koordinator melihat rekap gabungan. | PENDING | PENDING | PENDING |
| 3 | Tiga tab dapat dibuka langsung melalui URL. | PENDING | PENDING | PENDING |
| 4 | Pencarian nama dan filter kelas bekerja pada setiap tab. | PENDING | PENDING | PENDING |
| 5 | Filter Guru BK hanya tampil pada tab Layanan untuk Koordinator. | PENDING | PENDING | PENDING |
| 6 | Tabel desktop dan kartu mobile menampilkan nilai yang sama. | PENDING | PENDING | PENDING |
| 7 | Identitas sementara Layanan mempunyai penanda dan tidak tergabung karena nama. | PENDING | PENDING | PENDING |
| 8 | Cetak hanya memuat tab aktif. | PENDING | PENDING | PENDING |
| 9 | CSV memakai filter aktif dan tidak memuat data sensitif. | PENDING | PENDING | PENDING |
| 10 | Reset filter kembali ke tab aktif. | PENDING | PENDING | PENDING |
| 11 | Tidak ada horizontal overflow halaman. | PENDING | PENDING | PENDING |
| 12 | Urutan fokus mengikuti urutan visual dan focus ring terlihat. | PENDING | PENDING | PENDING |
| 13 | Endpoint legacy dapat dibuka oleh role sah. | PENDING | PENDING | PENDING |

Endpoint legacy sampel:

- `/reports/preview?type=pelanggaran-murid`
- `/reports/export?type=pelanggaran-murid&format=csv`

## Evidence otomatis

| Gate | Hasil |
|---|---|
| Focused SQLite | PASS — 53 tes, 553 assertion pada lima suite Task 7 |
| Rekap SQLite | PASS — 16 tes, 148 assertion |
| Rekap MySQL disposable `sibk_report_gate` | PASS — 16 tes, 148 assertion; instance dan folder sementara sudah dihapus |
| Full test | PASS — 418 tes, 3.293 assertion |
| Pint | PASS |
| Composer strict | PASS |
| Config cache dan view cache | PASS; cache sudah dibersihkan |
| Frontend checker | PASS |
| Build Vite | PASS |
| `git diff --check` | PASS |
| Scan field sensitif pada view/service rekap | PASS — tidak ada kecocokan |
| Scan dependency tabel terlarang | PASS — tidak ada kecocokan |

## Hasil akhir

Status tetap `PENDING MANUAL` sampai seluruh sel di atas diisi. Jika ada FAIL,
catat nomor skenario, viewport, langkah reproduksi, hasil aktual, dan hasil yang
diharapkan. Checkpoint 4 belum selesai dan Task 8 belum boleh dimulai sebelum
hasil perbaikan diuji ulang dan seluruh UAT dinyatakan PASS.
