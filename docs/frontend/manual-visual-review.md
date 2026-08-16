---
document_id: manual-visual-review
status: active
last_updated: 2026-08-16
---

# Review Visual Manual Frontend

## Tujuan dan prasyarat

Review ini dilakukan manusia setelah agen menyerahkan halaman dengan status `manual-visual-review-pending` dan melaporkan pemeriksaan teknisnya. Acuan visualnya adalah board atau ekspor Penpot halaman yang berstatus `hifi-approved`, bukan wireframe low-fidelity atau gambar `visual-direction-approved`.

Reviewer memakai data sintetis untuk semua data review, fixture, atau screenshot. Jangan memakai data murid nyata, data layanan BK, kredensial, atau informasi sensitif pada bukti review.

## Cakupan minimum

Untuk setiap `PG-*`, reviewer manusia memeriksa:

- desktop pada viewport referensi high-fidelity;
- tablet pada lebar `768px`;
- mobile pada lebar `390px`; dan
- seluruh state serta variasi peran yang tercantum pada brief halaman.

Pemeriksaan mencakup struktur, hierarki, tipografi, spacing, warna, border, radius, shadow, ikon, aset, kepadatan, overflow, fokus, label, kontras, responsif, serta tindakan yang terlihat untuk setiap peran. Periksa juga bahwa penggunaan Bootstrap, komponen bersama, dan token terpusat tetap menghasilkan tampilan yang sesuai board high-fidelity.

## Prosedur

1. Cocokkan `PG-*`, status `hifi-approved`, nama board/ekspor, state, dan peran dengan handoff agen serta brief.
2. Tinjau desktop referensi, tablet `768px`, dan mobile `390px` satu per satu.
3. Tinjau seluruh state/peran dalam cakupan, termasuk empty, error, loading, disabled, read-only, atau akses ditolak bila ada pada brief.
4. Catat setiap ketidaksesuaian memakai format temuan di bawah dan kirimkan koreksi kepada agen.
5. Ulangi review setelah koreksi. Simpan bukti review dengan data sintetis.

## Format temuan

Gunakan satu baris atau item untuk setiap temuan:

```text
PG: PG-xxx
Viewport: desktop referensi | 768px | 390px
Komponen: nama komponen atau area
Gejala: apa yang berbeda atau rusak
Hasil yang diharapkan: hasil yang harus sesuai dengan high-fidelity
```

## Persetujuan dan status

Reviewer manusia atau owner yang ditentukan mencatat persetujuan setelah seluruh cakupan minimum telah diperiksa dan koreksi yang diperlukan selesai. Hanya mereka yang boleh mengubah status dari `manual-visual-review-pending` menjadi `implemented`. Agen tidak mencentang pemeriksaan manusia atau mengklaim kesesuaian visual tanpa persetujuan ini.
