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

## Data Layer & Integrasi Backend

Gunakan pola:

`Blade View / Presentation → Web Controller (Form Request) → Service Layer → Eloquent Model`

Fase aktif saat ini:
- Menggantikan mock fixture dengan Controller terhubung ke database.
- Menyediakan form actions, CSRF token, session validation feedback, dan old input support.
- Menghubungkan authorization/policy checks di Blade (`@can`, `@if(auth()->user()->...)`).
- Komponen presentasi tetap modular dan tidak menanam query database langsung di view.

## Authorization

Menu, data, dan aksi pada antarmuka pengguna dikendalikan oleh authorization/policy backend (AUTH-01 s.d. AUTH-07). Jangan hardcode logika hak akses statis di tampilan.

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
