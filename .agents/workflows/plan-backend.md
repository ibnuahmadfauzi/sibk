# Plan Ruang BK Backend

Description: Menyusun rencana backend tanpa mengimplementasikan kode backend.

## Input
Nama modul/kebutuhan.

## Steps
1. Baca requirement terkait pada SRS.
2. Baca scope terkait pada PRD.
3. Identifikasi aksi yang dibutuhkan frontend.
4. Identifikasi entity/domain concept yang diperlukan.
5. Identifikasi authorization/capability.
6. Identifikasi source data: internal, Dapodik, e-Tatib.
7. Susun kandidat service boundary dan data flow.
8. Bila perlu, isi draft `docs/api-contract.md`.
9. Tandai keputusan belum final sebagai `TBD`.
10. Laporkan risiko privacy, audit, sinkronisasi, dan konsistensi data.

## Stop Condition

Berhenti pada tahap perencanaan.

Jangan membuat endpoint, migration, model, seeder, database, atau integrasi.
