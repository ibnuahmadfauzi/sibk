# Agent Documentation Foundation Implementation Plan

> **For agentic workers:** gunakan rencana ini sebagai jejak pembentukan fondasi dokumentasi. Langkah menggunakan checkbox untuk pemeriksaan ulang.

**Goal:** Menyediakan satu sumber konteks pengembangan Aplikasi BK yang dapat dibaca konsisten oleh Codex dan seluruh model Google Antigravity.

**Architecture:** Instruksi model di root hanya menjadi entrypoint. Seluruh aturan, kebutuhan, keputusan, dan workflow kanonik berada di `docs/` serta dipisahkan menurut produk, keamanan, frontend, backend, UX, dan proses pengembangan.

**Tech Stack:** GitHub Markdown, Codex `AGENTS.md`, Google Antigravity workspace rules, Bootstrap untuk frontend.

## Global Constraints

- Repository tunggal dengan branch integrasi `development`.
- Tahap aktif hanya frontend wireframe ke UI.
- Bootstrap menjadi fondasi UI.
- Token visual terpusat; tidak ada nilai warna tersebar.
- Keputusan UX boleh ditetapkan ahli dan dicatat.
- Keputusan domain yang belum sah tidak boleh diasumsikan.

---

### Task 1: Sumber produk kanonik

**Files:**

- Create: `docs/product/prd.md`
- Create: `docs/product/srs.md`
- Create: `docs/product/ui-inventory.md`
- Create: `docs/product/ui-field-actions.md`
- Create: `docs/security/access-matrix.md`
- Create: `docs/decisions/open-validation.md`

- [x] Turunkan baseline PRD, SRS, dan inventaris v1.0 ke Markdown.
- [x] Pertahankan ID requirement, halaman, item, akses, dan validasi.
- [x] Tambahkan metadata versi serta sumber.

### Task 2: Entrypoint lintas agen

**Files:**

- Create: `AGENTS.md`
- Create: `.agents/rules/00-project-context.md`
- Create: `docs/ai/00-start-here.md`
- Create: `docs/ai/agent-operating-rules.md`

- [x] Arahkan Codex dan Antigravity ke sumber yang sama.
- [x] Tetapkan kepakaran berdasarkan area tugas.
- [x] Tetapkan hierarki sumber dan protokol kerja.

### Task 3: Aturan frontend dan UX

**Files:**

- Create: `docs/frontend/README.md`
- Create: `docs/frontend/bootstrap-and-design-tokens.md`
- Create: `docs/frontend/ui-ux-guidelines.md`
- Create: `docs/frontend/wireframe-to-ui-workflow.md`
- Create: `docs/frontend/page-implementation-order.md`
- Create: `docs/frontend/qa-checklist.md`
- Create: `docs/ux/decision-log.md`

- [x] Tetapkan Bootstrap dan token visual terpusat.
- [x] Tetapkan proses wireframe ke UI serta urutan 26 antarmuka.
- [x] Tetapkan gerbang responsif, aksesibilitas, role, dan state.

### Task 4: Batas backend dan proses tim

**Files:**

- Create: `docs/backend/README.md`
- Create: `docs/backend/api-contract-rules.md`
- Create: `docs/development/workflow.md`
- Create: `docs/development/documentation-rules.md`
- Create: `docs/development/definition-of-done.md`
- Create: `docs/development/repository-audit-checklist.md`

- [x] Pisahkan pekerjaan frontend dan backend dalam satu repository.
- [x] Tetapkan workflow branch, commit, PR, review, dan merge.
- [x] Tetapkan audit repository sebagai langkah wajib sebelum adaptasi kode.

### Task 5: Verifikasi paket

- [x] Periksa seluruh tautan relatif.
- [x] Periksa konsistensi ID dan keputusan.
- [x] Pindai placeholder dan kontradiksi yang tidak berasal dari validasi terbuka.
- [x] Kemas struktur agar dapat disalin ke root repository.

### Task 6: Sumber wireframe dan transformasi UI

**Files:**

- Create: `docs/design/README.md`
- Create: `docs/design/wireframe-source.md`
- Create: `docs/design/wireframe-page-map.md`
- Create: `docs/design/wireframes/README.md`
- Create: `docs/design/ui-quality-bar.md`
- Modify: entrypoint agen, workflow frontend, QA, serta decision log terkait

- [x] Tetapkan file Penpot sebagai sumber struktur wireframe.
- [x] Petakan seluruh 26 ID halaman ke nama frame dan nama ekspor.
- [x] Tetapkan bahwa wireframe harus ditransformasikan menjadi UI produksi, bukan sekadar diwarnai ulang.
- [x] Tetapkan fallback ekspor untuk agen yang tidak dapat mengakses Penpot.
- [x] Tetapkan wireframe sebagai kerangka UX content-first dan ahli UI menyempurnakan visual tanpa mengabaikan maksud UX.

### Task 7: Optimasi konteks agen

**Files:**

- Modify: `AGENTS.md`
- Modify: `.agents/rules/00-project-context.md`
- Modify: `docs/ai/00-start-here.md`
- Modify: `docs/ai/agent-operating-rules.md`
- Create: `docs/ai/context-loading.md`

- [x] Hapus impor `@` dokumen panjang dari aturan Always On Antigravity.
- [x] Ubah entrypoint menjadi router konteks selektif.
- [x] Tetapkan pencarian berdasarkan ID dan batas awal empat dokumen domain.
- [x] Pertahankan dokumen kanonik tanpa memuat semuanya pada setiap tugas.
