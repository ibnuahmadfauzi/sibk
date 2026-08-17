# Setup Tim — Ruang BK + Antigravity

## Penpot MCP

Disarankan:
- Frontend Developer
- UI Designer

Opsional:
- PM
- Analyst

Tidak diperlukan untuk pekerjaan normal:
- Backend Developer
- validator pengguna

Setiap pengguna memakai MCP key Penpot miliknya sendiri.

## Rule per Peran

Semua anggota teknis:
- `AGENTS.md`
- `.agents/rules/project.md`

Frontend:
- `.agents/rules/frontend.md`
- `/implement-page`
- `/verify-page`

Backend:
- `.agents/rules/backend.md`
- `/plan-backend` hanya bila diminta
- jangan implementasi backend pada fase sekarang

## Git

Commit:
- `AGENTS.md`
- `.agents/rules/`
- `.agents/workflows/`
- `docs/`

Jangan commit:
- MCP key personal
- `.agents/mcp_config.json` berisi token
- credential lokal

## Fokus Fase Sekarang

1. frontend foundation;
2. reusable component;
3. implementasi 23 PG;
4. mock/service abstraction;
5. manual visual approval.

Belum dikerjakan:
- database;
- API final;
- auth server;
- connector Dapodik/e-Tatib;
- deployment backend.
