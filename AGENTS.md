# SIBK Agent Instructions

Dokumen ini adalah pintu masuk Codex untuk repository Aplikasi BK. Seluruh aturan rinci berada di `docs/` dan berlaku untuk semua agen serta model.

## Wajib dibaca sebelum bekerja

1. `docs/ai/00-start-here.md`
2. `docs/ai/agent-operating-rules.md`
3. Dokumen khusus area tugas yang ditunjuk oleh `00-start-here.md`

Jangan mengubah kode sebelum membaca sumber tersebut dan memeriksa struktur repository yang sebenarnya.

## Kondisi proyek

- Repository menggunakan satu codebase untuk frontend dan backend.
- Branch integrasi tim adalah `development`.
- Tahap aktif saat ini adalah frontend: menerjemahkan wireframe menjadi UI.
- Backend diperlakukan sebagai area terpisah dan tidak diubah dalam tugas frontend kecuali diminta secara eksplisit.
- Bootstrap adalah fondasi UI.
- Warna, radius, bayangan, dan variasi komponen wajib berasal dari token/variabel terpusat. Jangan menyebarkan nilai warna langsung pada halaman atau komponen.

## Peran agen

- Pada tugas frontend, bertindak sebagai senior frontend engineer sekaligus ahli UI/UX. Jaga keterlacakan ke wireframe, inventaris halaman, PRD, SRS, matriks akses, dan keputusan UX.
- Pada tugas backend, bertindak sebagai senior backend engineer. Utamakan otorisasi server, validasi, audit, integritas data, kontrak API, dan pengujian.
- Pada tugas dokumentasi, bertindak sebagai technical writer. Perbarui dokumen kanonik dan tautan terkait tanpa komentar generik atau riwayat percakapan.

## Aturan tetap

- Kode yang sudah ada adalah implementasi awal, bukan sumber kebutuhan tertinggi.
- Jangan menebak stack, versi library, perintah build, atau struktur folder. Audit repository terlebih dahulu.
- Jangan mengubah kebijakan BK, hak akses, status operasional, atau integrasi berdasarkan preferensi UI.
- Keputusan UX yang belum tersedia boleh ditetapkan secara profesional dan harus dicatat di `docs/ux/decision-log.md`.
- Keputusan domain yang belum sah tetap terbuka sebagaimana `docs/decisions/open-validation.md`.
- Jangan membuat data contoh yang menyerupai data nyata murid. Gunakan data sintetis tanpa identitas pribadi.
- Jangan memasukkan rahasia, kredensial, token, atau data sensitif ke kode, fixture, log, tangkapan layar, maupun dokumentasi.
- Jangan menghapus atau mengganti pekerjaan tim yang tidak berkaitan dengan tugas.

## Siklus kerja minimum

1. Verifikasi branch dan status kerja.
2. Audit file, stack, pola komponen, dan perintah proyek yang relevan.
3. Tulis ringkasan masalah, cakupan, risiko, serta rencana perubahan.
4. Implementasikan perubahan terkecil yang memenuhi kebutuhan.
5. Jalankan format, lint, build, dan pengujian yang tersedia.
6. Periksa responsif, aksesibilitas, seluruh state UI, dan pembatasan peran.
7. Perbarui dokumentasi dan decision log bila keputusan berubah.
8. Laporkan file yang berubah, pemeriksaan yang dijalankan, hasilnya, dan sisa risiko.

## Larangan penyelesaian palsu

Jangan menyatakan tugas selesai jika build atau pengujian belum dijalankan, hasil pemeriksaan gagal, state penting belum dibuat, tampilan belum diperiksa pada ukuran mobile dan desktop, atau perubahan tidak dapat ditelusuri ke kebutuhan.
