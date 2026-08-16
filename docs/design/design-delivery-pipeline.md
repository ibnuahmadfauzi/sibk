---
document_id: design-delivery-pipeline
version: 1.1
status: approved
last_updated: 2026-08-16
---

# Alur Wireframe, UI, dan Implementasi Frontend

## Tujuan

Dokumen ini menetapkan pemisahan yang jelas antara kebutuhan produk, wireframe low-fidelity, UI high-fidelity, dan implementasi frontend. Tujuannya adalah mengurangi lompatan keputusan, menjaga konsistensi ketika pekerjaan berpindah agen, dan memastikan frontend tidak menebak rancangan visual dari wireframe.

## Keputusan utama

1. Wireframe low-fidelity tidak lagi menjadi sumber visual langsung untuk implementasi frontend.
2. Wireframe low-fidelity yang disetujui menjadi kontrak UX untuk informasi, alur, aksi, state, hierarki, dan batas peran.
3. UI high-fidelity yang disetujui menjadi sumber visual resmi frontend.
4. Setiap unit antarmuka memakai ID `PG-*` yang sama pada kebutuhan, Penpot, ekspor, implementasi, test, dan pull request.
5. Catatan agen, petunjuk teknis, alasan desain, dan instruksi penggunaan tidak ditempatkan di dalam board wireframe atau UI yang menjadi artefak produk.
6. Wireframe dan UI dikerjakan serta diperiksa satu unit antarmuka pada satu waktu.
7. Tiga contoh visual yang disetujui pemilik proyek menjadi `visual-direction-approved`, bukan desain halaman final.
8. Agen frontend menjalankan verifikasi teknis, sedangkan kesesuaian visual terhadap high-fidelity diperiksa manual oleh tim untuk menghemat konteks dan kuota model.

## Status contoh visual awal

Tiga contoh berikut menjadi arah visual resmi SIBK:

| Contoh | Pemetaan | Status | Fungsi |
|---|---|---|---|
| [`references/PG-001-login-visual-direction.png`](references/PG-001-login-visual-direction.png) | `PG-001 — Login` | `visual-direction-approved` | Arah login, branding, ilustrasi, form, dan komposisi dua area. |
| [`references/PG-002-dashboard-visual-direction.png`](references/PG-002-dashboard-visual-direction.png) | `PG-002 — Dashboard` | `visual-direction-approved` | Arah app shell, kartu ringkasan, daftar kerja, aktivitas, dan quick action. |
| [`references/PG-202-profil-murid-visual-direction.png`](references/PG-202-profil-murid-visual-direction.png) | `PG-202 — Profil Murid` | `visual-direction-approved` | Arah profil, tab, tabel layanan, badge status, dan ringkasan identitas. |

Contoh tersebut menetapkan karakter visual, tetapi belum menjadi ukuran presisi implementasi. Sebelum memperoleh status `hifi-approved`, desain halaman harus diperbaiki dan dilengkapi di Penpot.

Perbaikan minimum terhadap contoh:

- gunakan identitas resmi SMKN 1 Surabaya secara konsisten;
- gunakan istilah **murid**, bukan siswa;
- perbaiki salah ketik dan microcopy;
- gunakan satu logo serta satu gaya ikon yang disahkan;
- hapus pembatas atau elemen yang tidak memiliki fungsi, seperti teks `atau` tanpa metode login alternatif;
- pastikan aksi tabel memiliki hubungan objek yang jelas;
- sediakan desktop, tablet, dan mobile;
- sediakan state serta variasi peran yang diwajibkan brief;
- pastikan aset ilustrasi resmi, konsisten, dan dapat digunakan oleh proyek.

## Karakter visual yang dikunci

UI SIBK menggunakan arah berikut:

- nuansa tenang, ramah, aman, dan profesional;
- dominasi biru lembut, navy, putih hangat, dan krem;
- soft UI ringan pada permukaan, tanpa mengurangi kontras;
- sudut membulat, border tipis, dan shadow halus;
- whitespace cukup dengan kepadatan informasi yang tetap efisien;
- ikon outline yang konsisten;
- ilustrasi konseling dan unsur tanaman sebagai pendukung, bukan pusat tugas;
- warna status semantik yang selalu disertai teks atau ikon;
- tipografi yang mudah dipindai pada dashboard dan halaman data padat.

Nilai warna, font, radius, spacing, border, dan shadow belum boleh disalin dari perkiraan screenshot. Nilainya harus berasal dari foundations high-fidelity yang telah disahkan dan dipetakan ke design tokens repository.

## Alternatif struktur yang dipertimbangkan

### Satu Penpot Page untuk setiap `PG-*`

Struktur ini memberi pemisahan maksimum, tetapi menghasilkan terlalu banyak Penpot Page, menyulitkan pemeliharaan shell dan komponen bersama, serta memperlambat perpindahan antarhalaman satu modul.

### Seluruh antarmuka dalam satu Penpot Page

Struktur ini sederhana pada awal pengerjaan, tetapi akan menjadi kanvas yang terlalu besar, sulit dicari, dan rawan mencampurkan artefak low-fidelity, high-fidelity, state, serta dokumentasi.

### Penpot Page per modul dengan board per `PG-*`

Struktur ini dipilih. Setiap Penpot Page menampung satu modul, sedangkan setiap antarmuka menjadi satu keluarga board beridentitas `PG-*`. Struktur ini menjaga keterlacakan per halaman tanpa memecah desain menjadi terlalu banyak Penpot Page.

## Rantai sumber kebenaran

Urutan berikut menentukan kewenangan setiap sumber:

1. PRD, SRS, decision log, dan access matrix menentukan tujuan, aturan domain, data, dan kewenangan.
2. Inventaris UI dan daftar field/aksi menentukan unit antarmuka serta cakupan minimum setiap `PG-*`.
3. Brief desain halaman menentukan kontrak UX yang akan diterapkan pada satu keluarga board.
4. Wireframe low-fidelity yang disetujui menentukan struktur informasi, alur, aksi, state, dan hierarki UX.
5. UI high-fidelity yang disetujui menentukan komposisi visual, komponen, tipografi, warna, spacing, ikon, dan perilaku responsif.
6. Kode frontend menerapkan UI high-fidelity dengan Bootstrap dan design tokens repository.

Sumber pada tingkat lebih rendah tidak boleh mengubah keputusan sumber yang lebih tinggi. Jika ditemukan konflik, pekerjaan dihentikan pada bagian yang konflik, lalu perbedaannya dicatat pada decision log yang sesuai.

## Struktur Penpot

### Area low-fidelity approved

- `21 — WF Approved — Global`
- `22 — WF Approved — Guru BK`
- `23 — WF Approved — Murid & Profil`
- `24 — WF Approved — Laporan`
- `25 — WF Approved — Koordinator BK`
- `26 — WF Approved — Waka Kesiswaan`
- `27 — WF Approved — Admin IT`

### Area high-fidelity

- `31 — UI Hi-Fi — Foundations`
- `32 — UI Hi-Fi — Global`
- `33 — UI Hi-Fi — Guru BK`
- `34 — UI Hi-Fi — Murid & Profil`
- `35 — UI Hi-Fi — Laporan`
- `36 — UI Hi-Fi — Koordinator BK`
- `37 — UI Hi-Fi — Waka Kesiswaan`
- `38 — UI Hi-Fi — Admin IT`

Nomor dapat disesuaikan jika Penpot telah memakai nomor tersebut. Nama fungsi dan pemisahan areanya harus tetap dipertahankan.

### Area yang tidak menjadi sumber implementasi

Penpot Page arsip, flow map, dokumentasi, validation notes, dan prototipe pilot lama tetap dipertahankan sebagai riwayat. Nama halamannya harus diawali `ARSIP —` atau `REFERENSI —`. Agen tidak boleh memakai area tersebut sebagai sumber frontend tanpa instruksi eksplisit.

## Aturan keluarga board

Setiap antarmuka memakai satu keluarga board dengan nama kanonik:

```text
PG-103 — Detail Kasus — Default
PG-103 — Detail Kasus — Waka Read-only
PG-103 — Detail Kasus — Empty
PG-103 — Detail Kasus — Error
```

Ketentuannya:

- ID dan nama utama mengikuti `docs/product/ui-inventory.md`.
- Suffix dipakai untuk state, peran, breakpoint, langkah, atau overlay; suffix tidak membuat ID halaman baru.
- Wizard seperti `PG-102` dapat memiliki suffix `Langkah 1`, `Langkah 2`, dan seterusnya dalam keluarga yang sama.
- Form dan overlay yang memiliki ID sendiri tetap dibuat sebagai keluarga board sesuai inventaris.
- Board utama tidak memuat legenda agen, prompt, alasan desain, status pengerjaan, catatan validasi, atau petunjuk teknis.
- Teks yang terlihat pengguna harus berupa microcopy produk yang realistis dan memakai data sintetis.
- Nama layer dan komponen boleh bersifat teknis karena hanya terlihat pada struktur editor, bukan pada antarmuka produk.

## Artefak pendamping di repository

Setiap `PG-*` memiliki brief ringkas yang dapat dimuat agen secara selektif. Brief ditempatkan di `docs/design/pages/PG-xxx.md` dan memuat:

- tujuan halaman dan pengguna;
- requirement yang terkait;
- informasi, field, dan aksi wajib;
- batas akses dan data sensitif;
- state serta variasi peran yang wajib;
- board low-fidelity dan high-fidelity terkait;
- keputusan UX yang berlaku;
- status gerbang desain;
- masalah domain yang masih terbuka.

Brief tidak mengulang seluruh PRD atau SRS. Brief menautkan ID requirement dan hanya memuat konteks yang diperlukan halaman tersebut.

### Paket sumber high-fidelity per halaman

Sebelum halaman menjadi `hifi-approved`, paket desainnya harus memiliki:

- nama atau ID board Penpot yang kanonik;
- ekspor desktop, tablet, dan mobile;
- daftar state dan variasi peran;
- komponen serta token yang digunakan;
- aset logo, ikon, dan ilustrasi yang diperlukan;
- catatan perbedaan dari low-fidelity approved;
- kriteria penerimaan visual dan interaksi.

Screenshot saja tidak cukup menjadi kontrak implementasi. Bila nilai visual tidak dapat diperoleh dari Penpot atau token, agen harus menandainya sebagai kekurangan desain dan tidak membuat nilai permanen berdasarkan tebakan.

## Siklus satu halaman

### 1. Persiapan brief

- Pilih satu `PG-*` menurut urutan implementasi.
- Cari requirement, field, aksi, akses, dan keputusan yang terkait.
- Periksa frame lama hanya sebagai bahan audit, bukan baseline baru.
- Buat atau perbarui brief halaman.

**Gerbang:** cakupan halaman dan masalah domain yang belum sah dapat dibedakan dengan jelas.

### 2. Penyusunan wireframe low-fidelity

- Susun struktur informasi, urutan tugas, navigasi, aksi utama, aksi sekunder, dan progressive disclosure.
- Sertakan state yang memengaruhi pemahaman alur: default, kosong, gagal, read-only, dan akses ditolak bila relevan.
- Gunakan komponen netral tanpa keputusan visual dekoratif yang mengaburkan pengujian UX.
- Jangan menaruh dokumentasi atau komentar kerja di dalam board produk.

**Gerbang:** seluruh informasi serta aksi wajib tercakup, alur dapat dijalankan, variasi peran tidak melanggar access matrix, dan tidak ada keputusan domain yang dikarang.

### 3. Review wireframe

Review memeriksa kelengkapan, hierarki, beban kognitif, pencegahan kesalahan, navigasi, state, microcopy, dan batas akses. Perubahan dicatat pada brief atau UX decision log, bukan sebagai catatan permanen di atas board produk.

**Status keluaran:** `lowfi-approved`.

### 4. Penyusunan UI high-fidelity

- Mulai UI halaman setelah seluruh 26 keluarga wireframe mencapai `lowfi-approved`, sehingga navigasi, cakupan, dan hubungan lintas modul telah stabil.
- Duplikasi maksud UX dari wireframe approved, bukan tampilan mentahnya.
- Terapkan foundations, component library, design tokens, grid, tipografi, warna, ikon, spacing, dan responsif.
- UI designer boleh memperbaiki komposisi serta pola komponen selama informasi, tindakan, alur, dan kewenangan tidak berubah.
- Buat state visual yang diperlukan dan gunakan komponen yang dapat digunakan ulang.

**Gerbang:** UI memenuhi kontrak wireframe, standar visual, responsif, aksesibilitas, state, dan variasi peran.

### 5. Review high-fidelity

Bandingkan UI dengan brief, wireframe approved, access matrix, dan standar kualitas UI. Catat setiap perubahan UX dari wireframe beserta alasannya.

**Status keluaran:** `hifi-approved`.

### 6. Implementasi frontend

- Frontend hanya memulai implementasi visual halaman setelah status `hifi-approved`.
- Agen frontend membaca brief halaman, board atau ekspor high-fidelity, aturan Bootstrap, dan design tokens.
- Wireframe dibaca hanya untuk memeriksa maksud UX atau menyelesaikan ketidaksesuaian, bukan sebagai referensi visual utama.
- Implementasi harus mencakup state, responsif, aksesibilitas, dan variasi peran yang telah ditetapkan.
- Kontrak backend yang belum tersedia digantikan fixture sintetis dan ditandai sebagai dependency, bukan dikarang sebagai perilaku final.
- Untuk komponen atau halaman baru, gunakan Planning Mode dan kebijakan review manual pada Antigravity.
- Implementasikan fondasi dan komponen bersama sebelum merangkai halaman yang bergantung padanya.
- Jalankan build, test, linter, dan pemeriksaan console yang tersedia.
- Serahkan kesesuaian visual, responsif, dan perbandingan dengan high-fidelity kepada review manual tim.

**Gerbang:** implementasi lulus pemeriksaan visual, fungsional, responsif, aksesibilitas, build, dan test yang tersedia.

## Siklus Antigravity untuk frontend

### Persiapan

1. Gunakan feature branch dari `development` untuk satu halaman atau satu keluarga komponen.
2. Pilih Planning Mode dan `Request Review` untuk pekerjaan nontrivial.
3. Muat brief `PG-*`, high-fidelity approved, token, aset, dan QA yang relevan.
4. Audit stack serta komponen yang telah tersedia sebelum menulis kode.
5. Jelaskan rencana, file yang akan berubah, komponen yang akan dipakai, dan pemeriksaan teknis yang akan dijalankan.

### Implementasi

1. Kerjakan design tokens dan komponen bersama yang benar-benar diperlukan.
2. Gunakan Bootstrap sebagai fondasi struktur dan interaksi.
3. Gunakan aset proyek; jangan menggambar ulang logo atau ilustrasi melalui CSS.
4. Gunakan fixture sintetis yang mewakili state desain.
5. Hindari perubahan backend, hak akses, atau kontrak data di luar cakupan tugas frontend.

### Verifikasi teknis oleh agen

1. Jalankan aplikasi atau build sesuai perintah repository.
2. Jalankan formatter, linter, test komponen, dan test fungsional yang tersedia.
3. Pastikan tidak ada error build, import, route, atau console yang berasal dari perubahan.
4. Periksa struktur semantik, label, state, dan penggunaan token melalui kode serta test.
5. Laporkan perintah, hasil, file yang berubah, dan bagian yang masih menunggu review visual manual.

Agen tidak membuka browser, mengambil screenshot, membandingkan gambar, atau menjalankan visual regression kecuali diminta secara eksplisit untuk investigasi tertentu.

### Review visual manual oleh tim

Reviewer manusia memeriksa halaman pada desktop, tablet, dan mobile terhadap high-fidelity approved. Pemeriksaan meliputi struktur, hierarki, tipografi, spacing, warna, border, radius, shadow, ikon, aset, kepadatan, overflow, serta state penting.

Hasil review diberikan kembali sebagai daftar koreksi yang menyebutkan `PG-*`, viewport, komponen, gejala, dan hasil yang diharapkan. Agen memperbaiki daftar tersebut tanpa memuat ulang seluruh dokumentasi atau seluruh paket gambar.

Sebelum review manual selesai, handoff memakai status `manual-visual-review-pending`. Status berubah menjadi `implemented` hanya setelah pemeriksaan teknis lulus dan reviewer manusia menyetujui tampilan.

## Pembagian peran agen

### Agen UX/wireframe

Bertindak sebagai UX designer dan analyst. Fokus pada kebutuhan pengguna, arsitektur informasi, urutan tugas, state, microcopy, dan pencegahan kesalahan. Agen ini tidak menetapkan gaya visual final.

### Agen UI high-fidelity

Bertindak sebagai UI designer. Fokus pada komposisi visual, design system, komponen, konsistensi, responsif, dan aksesibilitas. Agen ini tidak mengubah kebutuhan produk atau hak akses.

### Agen frontend

Bertindak sebagai senior frontend engineer. Menerapkan UI high-fidelity approved dengan Bootstrap dan design tokens. Agen tidak mendesain ulang halaman secara sepihak dan tidak menggunakan wireframe lama sebagai sumber visual final.

### Agen backend

Bertindak sebagai senior backend engineer. Bekerja dari SRS, access matrix, model data, dan kontrak API. Agen backend tidak mengambil aturan domain dari tampilan Penpot.

## Aturan pemuatan konteks agen

Untuk satu halaman, agen memulai dengan paket konteks berikut:

1. `docs/design/pages/PG-xxx.md`;
2. bagian inventaris dan field/aksi dengan ID yang sama;
3. sumber desain sesuai tahap: low-fidelity untuk pekerjaan UX, high-fidelity untuk UI atau frontend;
4. satu pedoman domain yang relevan, seperti access matrix, UI quality bar, atau aturan Bootstrap.

Agen tidak membaca seluruh PRD, SRS, inventaris, atau seluruh file Penpot untuk tugas satu halaman kecuali ditemukan konflik lintas modul.

## Status gerbang desain

Gunakan salah satu status berikut pada brief halaman:

- `not-started`: belum dikerjakan;
- `brief-ready`: kontrak halaman sudah cukup untuk wireframe;
- `blocked-domain-validation`: brief atau desain tidak dapat disahkan karena keputusan domain belum tersedia;
- `lowfi-working`: wireframe sedang disusun;
- `lowfi-approved`: wireframe lulus review UX;
- `visual-direction-approved`: contoh menetapkan bahasa visual umum, tetapi belum menjadi sumber final satu halaman;
- `hifi-working`: UI high-fidelity sedang disusun;
- `hifi-approved`: UI siap menjadi sumber frontend;
- `frontend-working`: implementasi sedang berjalan;
- `manual-visual-review-pending`: implementasi teknis selesai dan menunggu pemeriksaan visual manusia;
- `implemented`: implementasi telah memenuhi Definition of Done.

Status tidak ditampilkan sebagai elemen visual pada board produk.

## Penanganan perubahan

- Perubahan produk memperbarui PRD/SRS terlebih dahulu, kemudian inventaris, brief, wireframe, UI, dan kode sesuai dampaknya.
- Perubahan UX memperbarui UX decision log, brief, wireframe, UI, dan kode.
- Perubahan visual tanpa dampak UX memperbarui foundations atau UI high-fidelity, kemudian kode dan bukti visual.
- Perubahan kode tidak boleh diam-diam menjadi keputusan desain baru. Ketidaksesuaian dikembalikan ke sumber desain yang tepat.

## Migrasi desain Penpot yang sudah ada

1. Pertahankan halaman lama sebagai arsip; jangan menghapusnya sebelum seluruh cakupan dipetakan.
2. Cocokkan board lama dengan 26 ID pada `docs/product/ui-inventory.md`.
3. Kerjakan keluarga board dalam urutan `docs/frontend/page-implementation-order.md`: global dan keamanan, layanan BK inti, data murid, laporan, tata kelola, lalu administrasi teknis.
4. Untuk setiap `PG-*`, ambil hanya struktur dan informasi yang masih sah.
5. Hilangkan catatan AI, petunjuk teknis, handoff, legenda validasi, dan teks yang bukan bagian produk dari board baru.
6. Lengkapi informasi, aksi, state, dan variasi peran berdasarkan brief halaman.
7. Review serta tetapkan `lowfi-approved` pada setiap keluarga board.
8. Mulai high-fidelity halaman setelah seluruh 26 keluarga board mencapai `lowfi-approved`. Foundations high-fidelity boleh disiapkan lebih awal, tetapi tidak menjadi alasan untuk melewati review wireframe.
9. Setelah high-fidelity halaman disetujui, perbarui sumber desain frontend ke board high-fidelity dan ekspornya.

Migrasi low-fidelity dilakukan per `PG-*` sampai seluruh baseline UX lengkap. High-fidelity kemudian dikerjakan per halaman atau per modul. Frontend hanya mengimplementasikan halaman yang sudah `hifi-approved`.

## Kriteria keberhasilan alur

- Seluruh 26 ID inventaris memiliki keluarga board low-fidelity yang terlacak.
- Setiap board produk bebas catatan agen dan petunjuk teknis.
- Setiap halaman frontend dapat ditelusuri ke brief serta board high-fidelity approved.
- Agen baru dapat melanjutkan satu halaman tanpa membaca seluruh dokumentasi proyek.
- Tidak ada kebutuhan, field, aksi, atau batas akses yang berubah hanya karena transformasi visual.
- Perbedaan antara wireframe, UI, dan kode tercatat dan dapat dijelaskan.
- Setiap implementasi halaman memiliki catatan hasil review visual manual pada viewport yang diperiksa.
- Agen tidak menetapkan token permanen dari perkiraan screenshot.
- Hasil frontend tetap konsisten ketika dikerjakan oleh model Antigravity yang berbeda.
