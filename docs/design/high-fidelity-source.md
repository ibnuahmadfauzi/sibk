---
document_id: high-fidelity-source
version: 1.0
status: active-reference
last_updated: 2026-08-16
---

# Sumber UI High-Fidelity

## Kontrak dan ruang kerja

Low-fidelity berstatus `lowfi-approved` adalah kontrak UX: informasi, alur, aksi, state, hierarki, dan batas peran. UI high-fidelity berstatus `hifi-approved` adalah satu-satunya kontrak visual untuk frontend. Frontend tidak menerjemahkan tampilan low-fidelity atau gambar arah visual langsung menjadi UI produksi.

Low-fi dan high-fi menggunakan file Penpot yang sama yang dicatat pada [wireframe-source.md](wireframe-source.md), tetapi dipisahkan menurut Page/modul. Area high-fidelity memakai struktur pada [design-delivery-pipeline.md](design-delivery-pipeline.md): `31 — UI Hi-Fi — Foundations`, lalu Page global, Guru BK, Murid & Profil, Laporan, Koordinator BK, Waka Kesiswaan, dan Admin IT. Area `21` sampai `27` tetap khusus low-fidelity; area arsip dan referensi bukan sumber frontend.

## Nama board dan status

Setiap keluarga board high-fi memakai format berikut:

```text
PG-* — Nama — Varian
```

Contoh varian yang sah adalah `Default`, `Desktop`, `Tablet`, `Mobile`, `Empty`, `Error`, atau `Waka Read-only`. Suffix menjelaskan breakpoint, state, peran, langkah, atau overlay; suffix tidak membuat ID halaman baru. Nama board yang tercantum pada [design-page-map.md](design-page-map.md) adalah nama kanonik yang direncanakan, bukan bukti bahwa board atau UUID Penpot sudah ada.

### `visual-direction-approved`

Status ini hanya berlaku untuk tiga gambar di [references/](references/README.md). Status dapat diuji dengan memastikan gambar tersebut memiliki pemetaan PG yang dicatat, dipakai hanya untuk karakter visual umum dan koreksi yang disepakati, serta **tidak** diperlakukan sebagai board, ekspor high-fi, atau kontrak pixel-perfect. Status ini tidak mengizinkan pengambilan nilai token atau detail responsif dari screenshot.

### `hifi-approved`

Status ini hanya boleh diberikan setelah review eksplisit membuktikan bahwa satu keluarga `PG-*`:

- berada pada Page high-fi modul yang tepat dan memakai nama board kanonik;
- mempertahankan kontrak UX `lowfi-approved` atau mencatat perubahan UX yang telah disetujui;
- memiliki desktop, tablet, dan mobile; state serta variasi peran yang diwajibkan brief;
- memakai foundations, komponen, token, logo, ikon, ilustrasi, dan aset yang telah disahkan;
- memiliki ekspor atau rujukan board yang dapat diperiksa bersama kriteria penerimaan visual dan interaksi; dan
- lulus pemeriksaan kualitas, responsif, aksesibilitas, serta batas akses oleh reviewer yang berwenang.

Tanpa seluruh bukti tersebut, gunakan `not-started` atau `hifi-working`; jangan gunakan `hifi-approved` dan jangan mulai implementasi visual frontend.

## Paket minimum per halaman

Sebelum status `hifi-approved`, paket setiap `PG-*` harus memuat:

- board desktop, tablet, dan mobile;
- default dan seluruh state relevan;
- variasi peran atau read-only yang diwajibkan;
- daftar komponen dan foundations/token yang digunakan;
- daftar aset logo, ikon, dan ilustrasi yang diperlukan;
- perbedaan dari low-fi yang disetujui beserta alasan UX bila ada; dan
- rujukan board atau ekspor yang dapat direview serta kriteria penerimaan visual/interaksi.

Nilai warna, font, spacing, radius, border, dan shadow yang exact hanya boleh diambil dari foundations/tokens Penpot yang telah disetujui, kemudian dipetakan ke [`docs/frontend/bootstrap-and-design-tokens.md`](../frontend/bootstrap-and-design-tokens.md). Jangan menebak atau menyalin nilai tersebut dari screenshot.

## Fallback akses

Jika Penpot tidak tersedia, catat nama board kanonik, paket yang masih hilang, dan status `not-started` atau `hifi-working` pada peta/brief; minta akses atau ekspor yang dapat diperiksa. Jika ekspor belum tersedia, rujukan board Penpot tetap diperlukan untuk review. Gambar `visual-direction-approved` dapat membantu memelihara arah umum, tetapi tidak menaikkan status menjadi `hifi-approved`. Tidak ada fallback yang mengizinkan agen menebak token, membuat UUID, atau menganggap desain approved.
