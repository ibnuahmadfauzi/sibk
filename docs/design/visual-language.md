---
document_id: visual-language
version: 1.0
status: approved-direction
last_updated: 2026-08-16
---

# Bahasa Visual SIBK

## Karakter yang disetujui

SIBK memakai karakter tenang, ramah, aman, dan profesional untuk konteks layanan BK. Arah utamanya adalah nuansa biru lembut, navy, putih hangat, dan krem; ikon outline yang konsisten; whitespace yang cukup untuk tugas padat; serta ilustrasi konseling dan unsur tanaman sebagai pendukung, bukan pusat tugas. Status semantik selalu disertai teks, ikon, atau pola lain.

Soft UI hanya boleh memberi karakter ringan pada permukaan, kartu, dan navigasi. Input, tabel, focus state, alert, dan status tetap membutuhkan batas, kontras, serta hierarki yang jelas. Dekorasi tidak boleh menurunkan keterbacaan, kontras, kepadatan layar kerja, maupun kemampuan memindai aksi utama.

Nilai warna, tipografi, spacing, radius, border, dan shadow tidak ditetapkan oleh dokumen ini dan tidak boleh diperkirakan dari gambar. Nilai exact hanya berasal dari foundations/tokens Penpot high-fi yang sudah disetujui dan pemetaan token frontend.

## Tiga contoh arah visual

| Referensi | Pemetaan | Yang menjadi arah | Status |
|---|---|---|---|
| [`PG-001-login-visual-direction.png`](references/PG-001-login-visual-direction.png) | PG-001 — Login | branding sekolah, ilustrasi, form, dan komposisi dua area | `visual-direction-approved` |
| [`PG-002-dashboard-visual-direction.png`](references/PG-002-dashboard-visual-direction.png) | PG-002 — Dashboard | app shell, kartu ringkasan, daftar kerja, aktivitas, dan quick action | `visual-direction-approved` |
| [`PG-202-profil-murid-visual-direction.png`](references/PG-202-profil-murid-visual-direction.png) | PG-202 — Profil Murid | profil, tab, tabel layanan, badge status, dan ringkasan identitas | `visual-direction-approved` |

Ketiga gambar ini hanya mengarahkan bahasa visual. Gambar bukan desain halaman final, bukan ekspor high-fi, bukan kontrak pixel-perfect, dan tidak dapat dipakai frontend sebagai pengganti board atau ekspor `hifi-approved`.

## Koreksi wajib ketika arah ini diterapkan

- Gunakan identitas sekolah resmi **SMKN 1 Surabaya**.
- Gunakan istilah **murid** secara konsisten pada microcopy dan contoh.
- Pastikan label riwayat dieja dengan benar.
- Gunakan satu gaya logo dan satu gaya ikon yang konsisten di seluruh modul.
- Hapus pemisah login `atau` bila tidak ada metode autentikasi alternatif.
- Pertahankan Soft UI sebagai aksen yang fungsional; jangan mengorbankan kontras, hierarki, keterbacaan, atau kepadatan layar kerja.

Kualitas visual dan batas implementasi diperiksa bersama [ui-quality-bar.md](ui-quality-bar.md), sedangkan sumber visual untuk frontend tetap mengikuti [high-fidelity-source.md](high-fidelity-source.md).
