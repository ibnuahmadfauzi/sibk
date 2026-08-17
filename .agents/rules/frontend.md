# Ruang BK — Frontend Rule

## Referensi Desain

- Board PG pada Penpot `22 — UI High-Fidelity Final`.
- Pola visual pada `22.5 — Style Guide`.
- Jangan gunakan wireframe low-fidelity sebagai acuan visual.

## Tipografi

`font-family: 'Inter', Arial, Helvetica, sans-serif;`

## Reusable Components

Gunakan kembali komponen yang setara sebelum membuat komponen baru:
- AppShell
- Sidebar
- Topbar
- GlobalSearch
- PageHeader
- Button
- Input
- Select
- Textarea
- Card
- StatusBadge
- Table
- EmptyState
- Modal

## Data Layer

Gunakan pola:

`UI/presentation → page/container → service/repository interface → adapter`

Fase sekarang:

`Page → Service Interface → Mock Adapter`

Fase backend nanti:

`Page → Service Interface → API Adapter`

Jangan menanam mock data permanen di komponen presentasi dan jangan membuat endpoint backend sebagai bagian dari task frontend.

## Authorization

Hi-Fi bersifat account-neutral. Menu, data, dan aksi aplikasi nyata mengikuti authorization/capability, bukan varian UI hardcoded per role.

## Backend Boundary

Frontend tidak memutuskan:
- skema database;
- endpoint final;
- payload final;
- autentikasi server;
- mekanisme sinkronisasi Dapodik/e-Tatib.

## Quality Gate

Setelah implementasi:
1. formatter;
2. lint;
3. build;
4. buka route;
5. cek browser console;
6. cek asset rusak;
7. cek overflow;
8. cek responsivitas dasar.

Persetujuan visual akhir dilakukan manual.
