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

Halaman frontend mengikuti urutan `hifi-approved` → pemeriksaan teknis → `manual-visual-review-pending` → persetujuan manusia → `implemented`. Agen hanya dapat menutup handoff pada status `manual-visual-review-pending`; status akhir ditetapkan manusia atau owner sesuai prosedur [review visual manual](../frontend/manual-visual-review.md).

### Lima gerbang wajib

- [ ] **Sumber desain:** `PG-*`, requirement, brief, dan board atau ekspor sumber dapat ditelusuri; status desain awal telah diverifikasi sebagai `hifi-approved`.
- [ ] **Pemeriksaan teknis agen:** formatter, linter, build, test, pemeriksaan route/import/komponen, serta pemeriksaan aksesibilitas statis yang tersedia telah lulus atau hasil/keterbatasannya dicatat secara aktual.
- [ ] **Handoff visual:** setelah pemeriksaan teknis lulus, agen menyerahkan pekerjaan dengan status `manual-visual-review-pending` dan tidak mengklaim tampilan telah selesai atau sesuai secara visual.
- [ ] **Data review:** fixture, data review, dan screenshot atau bukti review—bila dibuat—hanya memakai data sintetis; tidak ada data murid nyata atau data sensitif.
- [ ] **Persetujuan akhir:** review visual manual desktop, tablet, mobile, serta state/peran yang relevan tercatat; hanya reviewer manusia atau owner yang ditentukan boleh mengubah status menjadi `implemented`.

### Kualitas implementasi

- [ ] Bootstrap digunakan sebagai fondasi.
- [ ] Nilai visual berasal dari design tokens/CSS variables terpusat; tidak ada nilai visual berulang yang dihardcode atau inline.
- [ ] Komponen reusable tidak digandakan.
- [ ] State loading, kosong, gagal, berhasil, read-only, dan denied tersedia sesuai konteks.
- [ ] Semantik, keyboard, fokus, label, error, dan kontras telah diperiksa secara teknis sesuai stack.
- [ ] Setiap peran hanya melihat UI yang sah.
- [ ] Data sensitif tidak bocor pada UI, URL, log, atau fixture.

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
- `PG-*`, status desain awal, dan board/ekspor high-fidelity sumber untuk pekerjaan frontend;
- status handoff `manual-visual-review-pending`, data sintetis yang dipakai, dan risiko review visual yang masih terbuka;
- halaman/peran/ukuran layar yang diperiksa oleh reviewer manusia setelah review tercatat;
- keputusan baru dan lokasi catatannya;
- keterbatasan yang masih berlaku.
