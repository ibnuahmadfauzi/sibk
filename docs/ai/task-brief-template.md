# Template Brief Tugas Agen

Gunakan struktur ini pada issue, prompt, atau deskripsi pekerjaan agar handoff antaranggota dan agen tetap konsisten.

## Tujuan

Jelaskan satu hasil yang dapat diperiksa.

## Cakupan

- ID halaman: tuliskan `PG-*` yang terdampak.
- Requirement: tuliskan ID SRS yang terkait.
- Peran pengguna: tuliskan peran yang terdampak.
- Area kode: frontend, backend, dokumentasi, atau kombinasi yang disetujui.

## Sumber wajib

- Tautkan maksimal empat sumber awal yang paling relevan beserta ID/bagiannya.
- Tambahkan sumber lain hanya jika dependensi atau konflik ditemukan saat pengerjaan.
- Jangan meminta agen membaca seluruh folder `docs/`.

## Perilaku yang diharapkan

- Nyatakan kondisi awal, tindakan pengguna, respons sistem, dan hasil akhir.
- Nyatakan state loading, kosong, berhasil, gagal, read-only, dan akses ditolak bila relevan.

## Batas

- Tuliskan hal yang tidak boleh diubah.
- Tuliskan keputusan domain yang masih terbuka.
- Jangan memasukkan pekerjaan backend ke tugas frontend tanpa persetujuan.

## Kriteria penerimaan

- Gunakan pernyataan yang dapat diuji atau diperiksa secara visual.
- Sertakan ukuran layar dan peran yang harus diperiksa untuk UI.
- Sertakan perintah test/build yang harus lulus bila sudah diketahui dari repository.

## Handoff

- Ringkasan perubahan.
- File yang berubah.
- Pemeriksaan dan hasil.
- Keputusan baru beserta lokasi dokumentasinya.
- Risiko tersisa.
