# Template Brief Tugas Agen

Gunakan struktur ini pada issue, prompt, atau deskripsi pekerjaan agar handoff tetap konsisten.

## Tujuan

Jelaskan satu hasil yang dapat diperiksa.

## Cakupan

- ID halaman:
- Tahap: lowfi | hifi | frontend | backend
- Status desain saat mulai:
- State/peran dalam cakupan:
- Requirement: tuliskan ID SRS yang terkait.
- Area kode: frontend, backend, dokumentasi, atau kombinasi yang disetujui.

## Sumber wajib

### Tugas halaman: lowfi, hifi, atau frontend

- Board/ekspor sumber:
- Tautkan satu brief PG, satu sumber desain sesuai tahap, dan paling banyak dua pedoman awal beserta ID/bagiannya.

### Backend

- Tautkan requirement SRS dan baris access matrix yang relevan.
- Tautkan aturan atau kontrak API hanya bila tugas mengubah kontrak.
- Jangan memuat brief PG atau sumber desain kecuali cakupan lintas area menyatakannya secara eksplisit.

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
- Pemeriksaan teknis:
- Review visual manual: belum | disetujui | perlu koreksi
- Keputusan baru beserta lokasi dokumentasinya.
- Risiko tersisa.
