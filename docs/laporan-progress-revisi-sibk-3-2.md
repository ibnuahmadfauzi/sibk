# Laporan Progress Revisi SIBK 3.2

Tanggal pembaruan: 19 September 2026  
Target integrasi: `cobasidebar`  
Branch Checkpoint 7A: `revisi-sibk-3-2`

## Ringkasan status

Implementasi, review, perbaikan review, dan full gate Checkpoint 7A telah
selesai. Branch sudah dipush ke GitHub. Integrasi ke `cobasidebar` masih
menunggu pembuatan dan merge Pull Request karena konektor GitHub yang tersedia
tidak memiliki izin tulis. Branch `main` tidak disentuh.

Checkpoint 7B dan 7C belum dimulai agar cleanup runtime dan skema tetap
mengikuti urutan aman.

## Progress yang telah dilakukan

### 1. Kontrak produk dan data

- Kasus dan konsultasi memakai empat status aktif: `Diajukan`, `Diproses`,
  `Selesai`, dan `Diarsipkan`.
- Edit memperbarui record aktif; salinan penuh setiap versi tidak dibuat.
  Waktu perubahan terakhir tetap tersedia melalui `updated_at`.
- Audit append-only dipertahankan untuk tindakan sensitif, termasuk pembukaan
  detail oleh Waka dan perubahan data yang sudah selesai.
- Tanggal formulir otomatis memakai tanggal hari ini, tetapi tetap dapat diedit.
- Data selesai tetap dapat diedit setelah pengguna melewati peringatan dan
  memberikan alasan yang disimpan pada audit.
- Penghapusan/pengarsipan tetap memakai konfirmasi eksplisit.

### 2. Alur kasus dan konsultasi

- Alur pembuatan, pembaruan, penyelesaian, dan pengarsipan kasus diselaraskan
  dengan kontrak Revisi 3.2.
- Konsultasi mandiri disederhanakan tanpa menghilangkan kontrol otorisasi.
- Koordinasi dengan Waka dilakukan di luar aplikasi; aplikasi tidak lagi
  menjadi workflow koordinasi, tetapi Waka tetap dapat membaca detail kasus.
- Validasi dipindahkan ke Form Request dan aturan bisnis tetap berada di
  service/action layer.

### 3. Akses Waka dan privasi

- Waka dapat membaca seluruh layanan BK sesuai keputusan produk.
- Akses Waka bersifat hanya baca; seluruh mutasi tetap ditolak oleh policy dan
  pemeriksaan server.
- Detail yang ditampilkan memakai proyeksi allowlist, bukan serialisasi model
  mentah.
- Setiap pembukaan detail oleh Waka dicatat pada audit.
- Narasi layanan tidak dimasukkan ke ekspor massal CSV untuk mengurangi risiko
  penyebaran data sensitif.

### 4. Frontend dan autosave

- Formulir penting memakai autosave lokal pada perangkat/browser yang sama.
- Draft tidak disimpan ke database dan tidak memiliki masa simpan satu bulan.
- Draft dibersihkan setelah penyimpanan berhasil.
- Interaksi modal, konfirmasi edit data selesai, ikon dashboard, dan tampilan
  detail layanan diselaraskan dengan komponen existing.
- Filter kelas dan filter terkait tetap tersedia pada header tabel agar data
  dapat difilter dan diurutkan tanpa halaman tambahan.

### 5. Laporan dan consumer turunan

- Laporan layanan, dashboard, profil murid, monitoring Waka, rekap operasional,
  query turunan, dan adapter legacy telah memakai kontrak data target.
- Seeder dan fixture dummy disesuaikan agar pengembangan tidak bergantung pada
  data produksi.
- Consumer bisnis tidak lagi membaca struktur retired yang akan dihapus pada
  Checkpoint 7B dan 7C.

### 6. Review dan perbaikan

- Tiga lane Wave 2 dikerjakan dan direview terpisah: akses Waka, laporan, dan
  consumer turunan.
- Final review menemukan 5 temuan Important dan 7 temuan Minor.
- Satu fix wave menutup seluruh 12 temuan.
- Scoped re-review tidak menemukan temuan Critical atau Important baru.

## Hasil verifikasi Checkpoint 7A

Verifikasi terakhir pada branch sebelum penambahan laporan ini:

| Gate | Hasil |
|---|---|
| `composer test` | Lulus, 454 test dan 3.529 assertion |
| `php vendor/bin/pint --test` | Lulus |
| `npm run check:frontend` | Lulus, termasuk pemeriksaan draft dan record layanan |
| `npm run build` | Lulus |
| `composer validate --strict` | Lulus |
| `git diff --check` | Lulus |

Perubahan dokumentasi ini wajib melewati diff-check dan pemeriksaan status Git
sebelum commit. Full gate tidak perlu diulang apabila hanya laporan/handoff yang
berubah dan tidak ada perubahan kode, dependency, atau konfigurasi runtime.

## Risiko tersisa dan mitigasi

| Risiko | Mitigasi yang berlaku |
|---|---|
| Waka memperoleh akses ke narasi layanan sensitif | Akses hanya baca, proyeksi allowlist, audit setiap pembukaan, dan narasi tidak masuk ekspor massal |
| Draft hilang saat perangkat/browser berubah atau penyimpanan lokal dibersihkan | Autosave dinyatakan sebagai bantuan lokal, bukan penyimpanan server; pengguna tetap menyimpan formulir final ke aplikasi |
| Runtime lama masih dapat menjadi dependency tersembunyi | Checkpoint 7B wajib melakukan scan consumer sebelum menghapus class, route, service, dan view lama |
| Drop skema menghilangkan data retired secara permanen | Checkpoint 7C baru berjalan setelah 7B `MERGED`, scan dependency bersih, backup operasional tersedia, dan migration forward-only diverifikasi |
| Adapter Dapodik/e-Tatib belum tersedia | Driver production tetap `unavailable` sampai kontrak resmi provider lolos admission gate |

## Progress selanjutnya

### Penutupan Checkpoint 7A

1. Buat Pull Request `revisi-sibk-3-2` ke `cobasidebar`.
2. Pastikan base bukan `main`, lalu merge tanpa force push.
3. Verifikasi status PR `MERGED` dan commit hasil merge tersedia pada
   `origin/cobasidebar`.
4. Hapus branch sumber remote setelah merge; branch yang belum merge tidak
   boleh dihapus.

### Checkpoint 7B - cleanup runtime lama

Checkpoint 7B dibuat dari `cobasidebar` terbaru setelah 7A `MERGED`.

1. Buat branch `revisi-sibk-3-2-7b-runtime`.
2. Hapus model, service, controller, request, route, dan view runtime retired
   untuk follow-up, koordinasi kasus, private note, serta resolve terpisah.
3. Jalankan scan consumer runtime sampai tidak ada referensi lama.
4. Jalankan focused gate dan full gate.
5. Review, buka PR terpisah ke `cobasidebar`, verifikasi `MERGED`, hapus branch
   sumber remote, lalu pause.

### Checkpoint 7C - cleanup skema dan gate akhir

Checkpoint 7C dibuat dari `cobasidebar` terbaru setelah 7B `MERGED`.

1. Buat branch `revisi-sibk-3-2-7c-schema`.
2. Ulangi scan dependency; migration destruktif tidak boleh dibuat jika masih
   ada consumer runtime.
3. Tambahkan test skema final yang gagal terlebih dahulu.
4. Tambahkan migration forward-only untuk menghapus tabel/kolom retired dengan
   urutan foreign key yang aman.
5. Verifikasi migration fresh dan upgrade pada database disposable, bukan
   database shared/production.
6. Jalankan full gate, review, lalu integrasikan melalui PR terpisah ke
   `cobasidebar`.

## Posisi aman untuk pause

Posisi pause aman saat ini adalah branch `revisi-sibk-3-2` yang sudah dipush.
Jangan memulai 7B sebelum PR 7A berstatus `MERGED`. Jika batas penggunaan
mendekat, berhenti setelah commit bersih dan catat status terakhir pada
`docs/current-work.md`.
