# Analisis Hasil dan Pembahasan Pengujian RBAC Ruang BK

## 1. Identitas Artefak

| Komponen | Nilai |
|---|---|
| Judul penelitian | Perancangan role-permission dan pengujian enforcement akses fungsi/data sesuai kewenangan |
| Metode | Design-and-evaluation dengan scenario-based testing |
| Dataset | `2026-08-21.1` |
| Tanggal baseline | 21 Agustus 2026 |
| Workbook hasil | `docs/testing/bukti-rbac/RBAC-Test-Results-101032.xlsx` |
| Matriks desain | `docs/testing/rbac-role-permission-matrix.csv` |
| Matriks skenario | `docs/testing/rbac-scenario-matrix.csv` |
| Panduan pengujian | `docs/testing/rbac-manual-testing.md` |
| Automated companion | `AuthorizationResearchScenarioTest`: 14 test, 116 assertion, seluruhnya lulus |

Dokumen ini mengolah hasil pengujian menjadi bahan jawaban RQ1 dan RQ2. Data yang dianalisis adalah role, capability, resource, kondisi kewenangan, expected access, actual access, serta bukti respons. Penelitian tidak menggunakan responden sebagai sumber data utama.

## 2. Ringkasan Hasil

Sebanyak 41 skenario pengujian telah dieksekusi. Seluruh skenario memperoleh hasil aktual yang sesuai dengan expected access, sehingga tercatat 41 Pass, 0 Fail, dan 0 skenario belum diuji. Skenario terdiri atas 18 pengujian positif atau Allow dan 23 pengujian negatif atau Deny.

| Jenis skenario | Jumlah | Proporsi | Hasil sesuai |
|---|---:|---:|---:|
| Positif/Allow | 18 | 43,90% | 18 |
| Negatif/Deny | 23 | 56,10% | 23 |
| Total | 41 | 100,00% | 41 |

Komposisi tersebut memenuhi gate minimum penelitian karena pengujian tidak hanya menunjukkan fungsi yang berhasil diakses, tetapi juga menguji penolakan terhadap fungsi dan data di luar kewenangan.

### Hasil per requirement

| Requirement | Fokus enforcement | Skenario | Pass | Fail | Kesesuaian |
|---|---|---:|---:|---:|---:|
| AUTH-01 | Autentikasi, akun aktif, dan pencabutan sesi | 5 | 5 | 0 | 100% |
| AUTH-02 | Scope profesional Guru BK | 8 | 8 | 0 | 100% |
| AUTH-03 | Pemeriksaan per objek, nested resource, dan kepemilikan | 4 | 4 | 0 | 100% |
| AUTH-04 | Kerahasiaan konsultasi | 6 | 6 | 0 | 100% |
| AUTH-05 | Akses Waka terbatas dan read-only | 7 | 7 | 0 | 100% |
| AUTH-06 | Pemisahan hak teknis Admin IT | 6 | 6 | 0 | 100% |
| AUTH-07 | Pemisahan fungsi pada akun multi-role | 5 | 5 | 0 | 100% |
| **Total** |  | **41** | **41** | **0** | **100%** |

Persentase kesesuaian dihitung dengan rumus:

`Kesesuaian = jumlah skenario Pass / jumlah skenario yang diuji × 100%`

Nilai 100% hanya menyatakan kesesuaian pada 41 skenario dan resource yang diuji. Nilai tersebut tidak dapat digunakan untuk menyimpulkan bahwa aplikasi aman secara keseluruhan.

## 3. Jawaban RQ1: Perancangan Role-Based Access Control

### RQ1

> Bagaimana Role-Based Access Control dirancang berdasarkan kebutuhan hak akses dan privasi pada aplikasi layanan BK berbasis web?

RBAC dirancang menggunakan empat role P0, yaitu Guru BK, Koordinator BK, Waka Kesiswaan, dan Admin IT. Role menentukan capability global, sedangkan kewenangan terhadap data ditentukan kembali berdasarkan kondisi resource. Dengan demikian, kepemilikan role saja tidak selalu cukup untuk membuka suatu data.

Model ini dapat diringkas sebagai:

`Role → Capability → Resource Predicate → Policy/Scope → Allow atau Deny`

### Pembagian role dan kewenangan

| Role | Capability utama | Batas data/privasi |
|---|---|---|
| Guru BK | Mengakses murid, kasus, konsultasi, tindak lanjut, dan laporan profesional | Hanya murid dalam penugasan kelas aktif atau kasus dengan penugasan khusus; perubahan profesional memerlukan kewenangan pada objek terkait. |
| Koordinator BK | Mengelola penugasan serta melihat rekap tata kelola | Metadata dan ringkasan umum dapat dibaca, tetapi role Koordinator tidak otomatis membuka catatan konsultasi privat. |
| Waka Kesiswaan | Membaca kasus dan informasi tertentu untuk koordinasi | Hanya kasus yang secara eksplisit dikoordinasikan kepada akun Waka tersebut; akses bersifat read-only dan tidak membuka konsultasi sensitif. |
| Admin IT | Mengelola akun, integrasi, dan Data Master | Hak teknis dipisahkan dari hak membaca kasus, profil layanan murid, konsultasi, prestasi, dan laporan BK. |
| Guru BK + Koordinator | Menjalankan capability dari kedua fungsi | Capability dihitung terpisah; fungsi Koordinator tidak memperluas scope privat fungsi Guru BK. |

### Lapisan enforcement

Perancangan tidak berhenti pada penyembunyian menu atau tombol. Enforcement dilakukan berlapis pada server:

1. **Autentikasi dan status akun** memastikan data operasional hanya tersedia bagi pengguna dengan sesi sah dan akun aktif.
2. **Gate atau capability global** menentukan apakah role memiliki fungsi umum, seperti pengelolaan akun atau penugasan.
3. **Query scope** membatasi daftar, pencarian, dashboard, profil, laporan, dan CSV pada kumpulan data yang berhak dibaca pengguna.
4. **Policy per objek** memeriksa kewenangan saat pengguna mengakses URL detail atau menjalankan tindakan pada resource tertentu.
5. **Scoped/nested binding** memastikan resource anak benar-benar milik resource induk pada URL dan mencegah pertukaran ID.
6. **Redaksi per bagian data** memisahkan metadata umum dari catatan privat atau internal.
7. **Audit** mencatat tindakan domain yang berhasil tanpa menyalin isi sensitif ke log.

Desain tersebut lebih tepat disebut RBAC dengan policy per objek dan scope kontekstual. Role tetap menjadi dasar pemberian capability, tetapi keputusan final juga mempertimbangkan penugasan aktif, periode efektif, kepemilikan, hubungan kasus, status data, dan koordinasi.

### Predicate resource yang diuji

| Predicate | Contoh keputusan |
|---|---|
| Penugasan kelas aktif | Guru A dapat membaca Alpha pada XII-A. |
| Penugasan kelas belum berlaku | Guru A ditolak saat membuka Delta dan `K-RBAC-005`. |
| Penugasan kasus khusus | Guru A dapat membuka `K-RBAC-003` walaupun Beta berada di kelas Guru B. |
| Kewenangan khusus per kasus | Akses ke `K-RBAC-003` tidak otomatis membuka `K-RBAC-002` milik murid yang sama. |
| Kepemilikan notification | Guru A dapat membuka notifikasinya sendiri dan ditolak dari notifikasi Guru B. |
| Hubungan parent-child | Tindak lanjut milik `K-RBAC-002` menghasilkan 404 ketika dipasang pada URL `K-RBAC-001`. |
| Scope privat konsultasi | Guru BK yang sah dapat membaca isi privat; Koordinator hanya memperoleh metadata jika tidak memiliki scope Guru BK. |
| Koordinasi Waka | Waka A dapat membaca `K-RBAC-002`; Waka B ditolak dari kasus yang sama. |
| Status prestasi | Waka hanya dapat membaca prestasi terverifikasi milik murid terkoordinasi. |
| Fungsi teknis | Admin IT dapat mengelola akun dan Data Master, tetapi ditolak dari layanan BK. |

### Kesimpulan RQ1

Perancangan RBAC pada Ruang BK menggabungkan matriks role-capability dengan enforcement berbasis resource. Empat role utama memperoleh fungsi yang berbeda, kemudian setiap akses data diperiksa kembali berdasarkan scope profesional, periode penugasan, penugasan kasus, kepemilikan, koordinasi, status resource, dan sensitivitas field. Pendekatan tersebut menjaga agar capability global tidak otomatis menjadi akses tanpa batas terhadap data layanan BK.

## 4. Jawaban RQ2: Hasil Pengujian Allow/Deny

### RQ2

> Bagaimana hasil pengujian skenario Allow/Deny menunjukkan penerapan aturan akses sesuai kewenangan pengguna?

Hasil pengujian menunjukkan bahwa seluruh 18 skenario positif menghasilkan akses yang diizinkan sesuai batasnya, sedangkan seluruh 23 skenario negatif menghasilkan penolakan yang diharapkan. Penolakan muncul dalam bentuk redirect login, HTTP 403, atau HTTP 404 untuk nested resource yang sengaja disembunyikan. Pada skenario Allow terbatas, halaman dapat dibuka tetapi marker privat atau internal tidak muncul.

### Analisis skenario kunci

#### Scope Guru BK tidak hanya ditentukan role

RBAC-009, RBAC-010, dan RBAC-011 membentuk pasangan pembanding utama. Guru A dapat membuka `K-RBAC-001`, ditolak dari `K-RBAC-002`, dan dapat membuka `K-RBAC-003` karena penugasan khusus. Ketiga kasus diuji menggunakan role yang sama. Perbedaan hasil membuktikan bahwa keputusan akses ditentukan oleh hubungan pengguna dengan resource, bukan hanya keberadaan role `guru_bk`.

RBAC-012 menambahkan dimensi waktu. Penugasan Guru A pada kelas Delta baru berlaku 90 hari setelah baseline sehingga akses ke `K-RBAC-005` ditolak. Hasil ini menunjukkan bahwa record penugasan yang ada di database belum dianggap aktif sebelum tanggal efektifnya.

#### Scope daftar dan laporan konsisten

RBAC-006 menunjukkan bahwa daftar Guru A hanya memuat Alpha dan Beta. Alpha masuk melalui penugasan kelas, sedangkan Beta masuk melalui penugasan kasus khusus. Gamma dan Delta tidak tampil karena berada di luar scope aktif pada baseline.

RBAC-013 menguji enforcement pada laporan. Laporan menampilkan `K-RBAC-001`, `K-RBAC-003`, `K-RBAC-004`, `KNS-RBAC-001`, dan `KNS-RBAC-002`, tetapi mengecualikan `K-RBAC-002`, `K-RBAC-005`, `KNS-RBAC-003`, serta marker `RBAC-PRIVATE` dan `RBAC-INTERNAL`. Hasil ini mendukung kesimpulan bahwa pembatasan tidak hanya diterapkan pada halaman detail, tetapi juga pada keluaran rekap.

#### Pemeriksaan objek dan pencegahan IDOR

RBAC-014 memasangkan ID tindak lanjut milik `K-RBAC-002` dengan URL induk `K-RBAC-001`. Server menghasilkan HTTP 404. Respons tersebut menunjukkan bahwa nested binding memeriksa hubungan resource anak dengan induknya dan tidak hanya memeriksa apakah kedua ID tersedia.

RBAC-015 dan RBAC-016 membandingkan notifikasi milik sendiri dan notifikasi pengguna lain. Notifikasi Guru A mengarahkan pengguna ke `K-RBAC-003`, sedangkan notifikasi Guru B ditolak. Dengan demikian, URL langsung tidak melewati pemeriksaan kepemilikan.

#### Redaksi data sensitif

RBAC-018 dan RBAC-019 membuka konsultasi yang sama menggunakan dua fungsi berbeda. Guru A dapat melihat marker `RBAC-PRIVATE-A`, sedangkan Koordinator hanya melihat nomor konsultasi dan ringkasan umum. Perbedaan keluaran pada resource yang sama membuktikan bahwa enforcement juga diterapkan pada bagian data, bukan hanya pada akses halaman.

RBAC-022 dan RBAC-023 menunjukkan kesinambungan histori. Akun multi-role dapat membaca histori privat Gamma karena kini memiliki scope Guru BK yang sah, tetapi tidak dapat mengubah catatan lama karena bukan Guru BK pencatat. RBAC-024 selanjutnya menunjukkan bahwa fungsi Koordinator pada akun yang sama hanya membuka metadata konsultasi Alpha dan tidak membuka marker privatnya.

#### Waka bersifat per objek dan read-only

RBAC-025 mengizinkan Waka A membaca `K-RBAC-002` tanpa catatan internal. RBAC-026 menolak Waka A dari kasus tanpa koordinasi dan RBAC-027 menolak Waka B dari kasus yang dikoordinasikan kepada Waka A. RBAC-028 menolak akses Waka A ke form penyelesaian kasus yang boleh dibacanya. Kombinasi tersebut menunjukkan bahwa koordinasi memberi kewenangan baca pada objek target tanpa memberikan kewenangan perubahan.

RBAC-029 sampai RBAC-031 memperlihatkan batas tambahan: Waka tidak dapat membaca laporan konsultasi, dapat membaca prestasi terverifikasi milik murid terkoordinasi, dan ditolak dari prestasi yang masih menunggu verifikasi.

#### Hak teknis Admin IT terpisah dari layanan BK

RBAC-032 dan RBAC-033 mengizinkan Admin IT membuka pengelolaan akun serta Data Master. Sebaliknya, RBAC-034 sampai RBAC-037 menolak Admin IT dari kasus, profil layanan murid, laporan, dan prestasi. Hasil ini menunjukkan bahwa privilege teknis tidak berubah menjadi privilege membaca layanan sensitif.

#### Multi-role tidak memperluas data privat secara otomatis

RBAC-038 dan RBAC-041 menunjukkan fungsi Koordinator untuk pengelolaan penugasan. RBAC-039 menunjukkan fungsi Guru BK akun multi-role pada profil Gamma, sedangkan RBAC-024 tetap meredaksi konsultasi Alpha yang berada di luar scope Guru BK akun tersebut. RBAC-040 menjadi kontrol negatif bahwa Guru A tanpa role Koordinator ditolak dari pengelolaan penugasan kelas.

### Kesimpulan RQ2

Pengujian menghasilkan kesesuaian 41 dari 41 skenario. Skenario positif menunjukkan fungsi dan data yang sah dapat diakses, sedangkan skenario negatif menunjukkan penolakan terhadap akun nonaktif, resource di luar scope, periode yang belum berlaku, resource milik pengguna lain, tindakan read-only, data privat, dan fungsi teknis yang tidak relevan. Hasil tersebut memberikan evidence bahwa aturan AUTH-01 sampai AUTH-07 diterapkan sesuai expected access pada endpoint dan resource yang diuji.

## 5. Pembahasan

### Enforcement UI dan server

Navigasi berbasis capability membantu pengguna menemukan fungsi yang sesuai, tetapi hasil penelitian tidak bergantung pada visibilitas menu. Skenario URL langsung membuktikan bahwa permintaan tetap diperiksa oleh server. Hal ini penting karena menu yang disembunyikan tidak mencegah pengguna mencoba URL secara manual.

### Allow tidak selalu berarti seluruh data terbuka

Beberapa skenario menghasilkan Allow terbatas atau redacted. Koordinator dapat membaca metadata konsultasi, Waka dapat membaca kasus terkoordinasi, dan laporan dapat memuat ringkasan umum. Namun, bagian privat dan internal tetap dikecualikan. Oleh karena itu, evaluasi tidak cukup hanya memeriksa HTTP 200; isi respons juga harus dibandingkan dengan `Must Appear` dan `Must Not Appear`.

### Deny memiliki beberapa bentuk respons

Redirect digunakan ketika pengguna belum memiliki sesi atau sesi akun aktif dicabut. HTTP 403 digunakan ketika identitas pengguna diketahui tetapi capability atau resource predicate tidak terpenuhi. HTTP 404 digunakan pada nested resource yang tidak cocok agar isi atau keberadaan resource anak tidak dibocorkan melalui URL induk yang salah. Ketiganya dikategorikan sebagai Deny, tetapi memiliki tujuan enforcement yang berbeda.

### Keseimbangan skenario positif dan negatif

Jumlah skenario negatif lebih banyak daripada skenario positif. Komposisi 23 Deny dan 18 Allow sesuai dengan tujuan pengujian enforcement karena kontrol akses perlu dibuktikan melalui jalur yang sah sekaligus percobaan akses di luar kewenangan. Tanpa skenario negatif, implementasi yang selalu mengizinkan akses juga dapat tampak berhasil.

### Reproduksibilitas

Dataset memakai penanda sintetis `RBAC-*`, versi dataset, tanggal baseline, URL aktual, dan command verifier. Periode penugasan dibentuk relatif terhadap waktu reset. Mekanisme tersebut memungkinkan pengujian diulang tanpa memakai data murid nyata dan mengurangi risiko URL lama digunakan setelah primary key berubah.

## 6. Validitas dan Kualitas Evidence

Validasi instrumen dilakukan melalui traceability antara requirement AUTH-01–AUTH-07, matriks role-permission, predicate resource, expected access, langkah pengujian, hasil aktual, serta evidence screenshot atau automated companion. Instrumen teknis tidak diperlakukan seperti kuesioner; kebenarannya diperiksa terhadap requirement dan business rule.

Seluruh 41 Scenario ID mempunyai satu file evidence dan tidak ditemukan ID yang hilang atau duplikat. Automated companion juga menunjukkan 14 test dan 116 assertion lulus, termasuk pemeriksaan baseline, scope, redaksi, nested binding, mutasi negatif, audit, serta kesamaan laporan dan CSV.

Sebelum workbook dijadikan lampiran final, terdapat dua housekeeping yang disarankan:

1. Ubah nama `RBAC-014_guru-a_Allow.png` menjadi `RBAC-014_guru-a_Deny-404.png` dan perbarui referensinya pada workbook. Isi screenshot sudah menunjukkan `404 Not Found`; ketidaksesuaian hanya terdapat pada nama file.
2. Isi kolom `Notes` untuk skenario yang memerlukan bukti perilaku lebih rinci, khususnya redirect, HTTP 403/404, dan redaksi field. Kolom Actual Access dapat tetap biner Allow/Deny, sedangkan Notes menjelaskan batas Allow atau bentuk Deny.

## 7. Keterbatasan dan Batas Klaim

- Pengujian hanya mencakup role, capability, resource, endpoint, periode, dan marker data yang tercantum dalam 41 skenario.
- Hasil tidak mencakup pengujian kerentanan umum seperti SQL injection, XSS, SSRF, konfigurasi jaringan, keamanan server, atau ketahanan terhadap serangan berskala besar.
- Screenshot membuktikan keluaran pada saat pengujian, sedangkan automated companion menjadi evidence tambahan untuk status HTTP dan perubahan database.
- Dataset seluruhnya sintetis sehingga hasil tidak mengevaluasi kualitas, kelengkapan, atau kesalahan data operasional nyata.
- Satu kali eksekusi oleh penguji utama belum mengukur konsistensi antar-penguji. Pengujian ulang oleh penguji kedua dapat digunakan untuk memperkuat reproduksibilitas, tetapi bukan syarat untuk menjawab kedua RQ.
- Nilai kesesuaian 100% tidak boleh ditafsirkan sebagai tingkat keamanan sistem secara keseluruhan.

Klaim yang didukung evidence adalah:

> Implementasi kontrol akses Ruang BK menghasilkan keputusan Allow/Deny yang sesuai dengan requirement AUTH-01 sampai AUTH-07 pada 41 skenario, endpoint, dan resource yang diuji.

## 8. Implikasi terhadap Tujuan Penelitian

| Tujuan | Evidence pencapaian |
|---|---|
| T1 — Merancang model RBAC berdasarkan requirement hak akses dan privasi | Matriks role-permission, empat role P0, predicate resource, serta enforcement berlapis melalui autentikasi, Gate/Policy, query scope, nested binding, dan redaksi field. |
| T2 — Menguji enforcement dengan skenario positif dan negatif | 18 skenario Allow dan 23 skenario Deny; seluruh 41 skenario sesuai expected access. |

## 9. Pemetaan Lima Aspek Projek Kepemimpinan

| Aspek | Implementasi/evidence dalam penelitian |
|---|---|
| Perencanaan | Perumusan role, capability, resource predicate, requirement AUTH-01–AUTH-07, dan matriks expected access. |
| Pelaksanaan | Implementasi policy/scope serta eksekusi scenario-based testing melalui UI, URL langsung, dan automated companion. |
| Hasil dan Dampak | Evidence 41/41 skenario sesuai, termasuk perlindungan catatan privat dan pemisahan hak teknis. |
| Kepemimpinan dan Sikap | Kehati-hatian dalam memberi kewenangan, penggunaan data sintetis, pemisahan tanggung jawab, dan pencatatan kegagalan tanpa menghapus evidence. |
| Refleksi dan Laporan | Batas klaim, housekeeping evidence, keterbatasan cakupan, serta rekomendasi pengujian ulang untuk reproduksibilitas. |

## 10. Narasi Ringkas Siap Adaptasi ke Artikel

### Hasil

Pengujian dilakukan terhadap 41 skenario yang memetakan role, capability, resource predicate, expected access, dan actual access. Skenario terdiri atas 18 pengujian positif dan 23 pengujian negatif yang mencakup autentikasi, scope Guru BK, pemeriksaan per objek, privasi konsultasi, akses Waka, pemisahan Admin IT, serta akun multi-role. Seluruh skenario memperoleh hasil sesuai expected access, dengan 41 Pass dan 0 Fail. Automated companion yang terdiri atas 14 test dan 116 assertion juga seluruhnya lulus.

### Pembahasan

Hasil menunjukkan bahwa keputusan akses tidak hanya bergantung pada role. Pada Guru BK, keputusan juga mempertimbangkan kelas aktif, periode penugasan, dan penugasan kasus khusus. Pada Koordinator, akses metadata tidak otomatis membuka catatan privat. Waka hanya memperoleh detail read-only untuk kasus yang dikoordinasikan kepadanya, sedangkan Admin IT tetap dibatasi pada fungsi teknis. Akun multi-role menjalankan capability Guru BK dan Koordinator secara terpisah sehingga fungsi tata kelola tidak memperluas scope data sensitif. Enforcement ditemukan konsisten pada navigasi, URL langsung, detail objek, nested resource, laporan, dan redaksi field yang diuji.

### Kesimpulan

Model RBAC Ruang BK dirancang melalui kombinasi role-capability dan pemeriksaan resource kontekstual. Pengujian Allow/Deny menunjukkan kesesuaian penerapan AUTH-01 sampai AUTH-07 pada seluruh 41 skenario. Kesimpulan ini terbatas pada kontrol dan resource yang diuji dan tidak menyatakan keamanan aplikasi secara keseluruhan.

## 11. Rekomendasi Tahap Berikutnya

1. Rapikan nama evidence RBAC-014 dan isi Notes pada workbook final.
2. Bekukan workbook, screenshot, matriks, dan versi dataset sebagai satu paket lampiran penelitian.
3. Buat checksum SHA-256 untuk workbook dan seluruh evidence agar integritas lampiran dapat diverifikasi.
4. Gunakan tabel hasil per requirement dan narasi RQ1/RQ2 dalam draft artikel.
5. Jika waktu memungkinkan, lakukan satu pengujian ulang oleh penguji kedua menggunakan baseline baru dan laporkan sebagai evidence reproduksibilitas, bukan sebagai data responden.
