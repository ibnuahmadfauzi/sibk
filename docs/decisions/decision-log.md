# Decision Log Aplikasi BK

Dokumen ini memuat keputusan yang telah dikunci. Perubahan hanya dilakukan setelah keputusan baru disetujui dan dampaknya diperbarui pada PRD, SRS, inventaris, access matrix, serta kode terkait.

| ID | Keputusan | Status | Dampak utama |
|---|---|---|---|
| DEC-001 | Koordinator BK adalah penanggung jawab operasional Aplikasi BK. | Dikunci | Menu penugasan, verifikasi, koordinasi, dan rekap berada pada fungsi Koordinator. |
| DEC-002 | Koordinator yang merangkap Guru BK tidak otomatis memperoleh akses konsultasi sensitif di luar scope Guru BK. | Dikunci | Fungsi Koordinator dan Guru BK diperiksa terpisah. |
| DEC-003 | Waka Kesiswaan hanya-baca; detail tersedia hanya pada kasus yang dikoordinasikan kepadanya. | Dikunci | Tidak ada aksi edit untuk Waka dan server tetap memeriksa objek kasus. |
| DEC-004 | Koordinasi Waka tidak otomatis membuka isi lengkap konsultasi, catatan profesional internal, atau dokumen asli. | Dikunci | Detail kasus harus dipisahkan per bagian dan sensitivitas. |
| DEC-005 | Guru BK dengan scope aktif dapat membaca histori layanan murid lintas kelas dan pergantian guru sampai murid lulus. | Dikunci | Profil murid menyediakan histori berkelanjutan. |
| DEC-006 | Pemegang scope baru tidak dapat mengubah catatan profesional Guru BK sebelumnya. | Dikunci | Riwayat lama bersifat read-only dan perubahan sah tetap diaudit. |
| DEC-007 | Dapodik menjadi sumber resmi murid, kelas, keanggotaan kelas, dan tahun ajaran. | Dikunci | Aplikasi BK tidak menjadi master alternatif. |
| DEC-008 | e-Tatib menjadi sumber baca-saja pelanggaran dan poin melalui API; tidak ada write-back pada MVP. | Dikunci | UI tidak menyediakan perubahan transaksi e-Tatib. |
| DEC-009 | Sebelum sinkronisasi, NISN dan nama sementara hanya dicatat ketika kasus atau layanan muncul, lalu direkonsiliasi berdasarkan NISN tanpa duplikasi. | Dikunci | Nilai awal dan hasil rekonsiliasi harus diaudit. |
| DEC-010 | Perubahan penugasan hanya dicatat Koordinator berdasarkan keputusan resmi; rolling dan pemindahan kasus aktif tidak otomatis. | Dikunci | Pengalihan kasus harus eksplisit dan menyimpan alasan serta waktu. |
| DEC-011 | Koordinator dapat merekap seluruh Guru BK aktif secara dinamis; Guru BK hanya merekap scope sendiri. | Dikunci | Jumlah tujuh Guru BK saat ini tidak boleh dihardcode. |
| DEC-012 | Admin IT menangani akun, infrastruktur, integrasi, master, dan rekonsiliasi tanpa akses otomatis ke isi layanan BK. | Dikunci | Hak teknis dipisahkan dari hak data layanan. |
| DEC-013 | Retensi minimum tiga tahun; penghapusan otomatis belum diaktifkan sampai kebijakan purge dan pemulihan disahkan. | Dikunci sebagian | Sistem tidak boleh membuat auto-delete dini. |
| DEC-014 | Wali kelas dan murid berada pada P1; prestasi tetap P0 bertahap setelah fungsi inti stabil. | Dikunci | Urutan implementasi tidak boleh mendahulukan fitur tersebut. |
| DEC-015 | Frontend dan backend berada dalam satu repository, tetapi dikelola sebagai area kerja dan dokumentasi terpisah. | Dikunci | Tugas harus menyebut area serta tidak memperluas scope diam-diam. |
| DEC-016 | Codex membaca `AGENTS.md`; seluruh model melalui Antigravity membaca `.agents/rules/`. | Dikunci | Isi rinci tetap satu sumber di `docs/`. |
| DEC-017 | Tahap pertama pengembangan adalah menerjemahkan wireframe menjadi UI frontend. | Historis — digantikan oleh DEC-022 | Catatan arah awal dipertahankan untuk jejak keputusan; rute aktif frontend kini memakai kontrak UX low-fidelity dan kontrak visual high-fidelity menurut DEC-022. |
| DEC-018 | Bootstrap menjadi fondasi frontend. Seluruh warna dan variasi komponen menggunakan token/variabel terpusat. | Dikunci | Tidak ada warna atau visual token tersebar pada halaman. |
| DEC-019 | Ketidakjelasan UX diputuskan melalui evaluasi ahli dan dicatat; keputusan domain tetap memerlukan pemilik proses. | Dikunci | UX dapat bergerak tanpa mengarang kebijakan BK. |
| DEC-020 | File Penpot yang dicatat pada `docs/design/wireframe-source.md` menjadi kerangka UX content-first untuk informasi, pengelompokan, prioritas, layout dasar, navigasi, dan alur; detail visual disempurnakan oleh ahli UI. | Dikunci | Setiap halaman menjaga maksud UX, menelusuri frame/ekspor, dan menghasilkan UI yang menarik, responsif, aksesibel, lengkap state, serta sesuai peran. |
| DEC-021 | Konteks agen dimuat secara selektif melalui router dan pencarian ID; dokumen panjang tidak diimpor pada aturan Always On. | Dikunci | Jumlah file boleh tetap lengkap, tetapi setiap tugas hanya membaca bagian yang relevan dan memperluas konteks berdasarkan risiko. |
| DEC-022 | Low-fidelity approved menjadi kontrak UX; high-fidelity approved menjadi kontrak visual frontend. | Dikunci | Frontend tidak lagi mengambil keputusan visual langsung dari wireframe. |
| DEC-023 | Tiga contoh PG-001, PG-002, dan PG-202 menjadi visual direction approved, bukan desain halaman final. | Dikunci | Nilai token dan detail responsif tetap harus disahkan pada foundations serta board high-fidelity Penpot. |
| DEC-024 | Review kesesuaian visual frontend dilakukan manual oleh tim. | Dikunci | Agen menjalankan verifikasi teknis dan berhenti pada status manual-visual-review-pending. |

## Cara menambahkan keputusan

1. Gunakan ID berikutnya.
2. Nyatakan keputusan dalam satu kalimat yang dapat diuji.
3. Catat status dan dampaknya.
4. Perbarui sumber turunan pada commit atau pull request yang sama.
