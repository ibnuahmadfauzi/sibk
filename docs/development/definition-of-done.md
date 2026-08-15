# Definition of Done

Perubahan hanya dinyatakan selesai jika seluruh bagian yang relevan terpenuhi.

## Semua perubahan

- [ ] Cakupan tugas dan sumber kebutuhan jelas.
- [ ] Perubahan hanya menyentuh area yang diperlukan.
- [ ] Tidak ada data pribadi, rahasia, atau kredensial yang ditambahkan.
- [ ] Formatter dan linter yang tersedia lulus.
- [ ] Test terkait lulus.
- [ ] Build yang relevan lulus.
- [ ] Tidak ada error baru yang diabaikan.
- [ ] Dokumentasi dan decision log tetap sinkron.
- [ ] Handoff menyatakan hasil aktual dan risiko tersisa.

## Frontend

- [ ] ID halaman dan requirement dapat ditelusuri.
- [ ] Bootstrap digunakan sebagai fondasi.
- [ ] Nilai visual berasal dari token terpusat.
- [ ] Komponen reusable tidak digandakan.
- [ ] State loading, kosong, gagal, berhasil, read-only, dan denied tersedia sesuai konteks.
- [ ] Mobile, tablet, desktop, dan zoom 200 persen telah diperiksa.
- [ ] Keyboard, fokus, label, error, dan kontras telah diperiksa.
- [ ] Setiap peran hanya melihat UI yang sah.
- [ ] Data sensitif tidak bocor pada UI, URL, log, atau fixture.
- [ ] Screenshot/review visual menggunakan data sintetis.

## Backend

- [ ] Validasi server tersedia.
- [ ] Otorisasi diuji untuk akses sah dan tidak sah.
- [ ] Perubahan penting menghasilkan audit.
- [ ] Transaksi dan integritas data dijaga.
- [ ] Kontrak API serta error didokumentasikan.
- [ ] Tidak ada data sensitif berlebih pada response atau log.
- [ ] Integrasi mengikuti sifat baca-saja/master yang ditetapkan.

## Dokumentasi

- [ ] Isi faktual, ringkas, dan bebas percakapan.
- [ ] ID, istilah, versi, serta tautan konsisten.
- [ ] Tidak ada keputusan domain baru yang disamarkan sebagai fakta.
- [ ] Dokumen lama diberi status yang jelas bila digantikan.

## Bukti selesai

Pull request atau handoff mencantumkan:

- file yang berubah;
- perintah pemeriksaan dan hasilnya;
- halaman/peran/ukuran layar yang diperiksa;
- keputusan baru dan lokasi catatannya;
- keterbatasan yang masih berlaku.
