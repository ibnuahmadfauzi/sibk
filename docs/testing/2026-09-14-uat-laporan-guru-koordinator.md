# UAT Laporan Guru BK dan Koordinator

Status: **PASS MANUAL**
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
| Nama/inisial tester | ui |
| Tanggal UAT | 15 September 2026 |
| Browser dan versi | Chrome — versi tidak dilaporkan |
| Branch/SHA | `checkpoint-4-laporan` / `af9e69a` |

## Checklist manual

Isi setiap sel dengan `PASS` atau `FAIL: <catatan singkat>`.

| No. | Skenario | 1440 × 900 | 768 × 1024 | 390 × 844 |
|---:|---|---|---|---|
| 1 | Guru BK hanya melihat murid dalam scope. | PASS | PASS | PASS |
| 2 | Koordinator melihat rekap gabungan. | PASS | PASS | PASS |
| 3 | Tiga tab dapat dibuka langsung melalui URL. | PASS | PASS | PASS |
| 4 | Pencarian nama dan filter kelas bekerja pada setiap tab. | PASS | PASS | PASS |
| 5 | Filter Guru BK hanya tampil pada tab Layanan untuk Koordinator. | PASS | PASS | PASS |
| 6 | Tabel desktop dan kartu mobile menampilkan nilai yang sama. | PASS | PASS | PASS |
| 7 | Identitas sementara Layanan mempunyai penanda dan tidak tergabung karena nama. | PASS | PASS | PASS |
| 8 | Cetak hanya memuat tab aktif. | PASS | PASS | PASS |
| 9 | CSV memakai filter aktif dan tidak memuat data sensitif. | PASS | PASS | PASS |
| 10 | Reset filter kembali ke tab aktif. | PASS | PASS | PASS |
| 11 | Tidak ada horizontal overflow halaman. | PASS | PASS | PASS |
| 12 | Urutan fokus mengikuti urutan visual dan focus ring terlihat. | PASS | PASS | PASS |
| 13 | Endpoint legacy dapat dibuka oleh role sah. | PASS | PASS | PASS |

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

Tester `ui` melaporkan seluruh skenario pada tiga viewport `PASS` melalui
Chrome pada 15 September 2026. Tidak ada temuan gagal yang dilaporkan.
Checkpoint 4 memenuhi gate otomatis dan manual; Task 8 dapat dimulai setelah
branch ini terintegrasi ke `cobasidebar`.
