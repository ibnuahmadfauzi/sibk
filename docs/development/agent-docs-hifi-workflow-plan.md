# SIBK Agent Docs High-Fidelity Workflow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use @subagent-driven-development (recommended) or @executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyelaraskan seluruh paket dokumentasi agen SIBK dengan alur PRD/SRS → low-fidelity approved → high-fidelity approved → frontend, menggunakan review visual manual dan pemuatan konteks selektif.

**Architecture:** Entrypoint agen tetap ringkas dan hanya merutekan konteks. Sumber desain dipisahkan menjadi low-fidelity untuk kontrak UX dan high-fidelity untuk kontrak visual. Setiap `PG-*` memperoleh brief kecil yang dapat dimuat sendiri, sedangkan review visual dilakukan manusia agar agen tidak memakai token untuk browser, screenshot, atau visual regression.

**Tech Stack:** Markdown, Penpot, Bootstrap, CSS custom properties/design tokens, Git, ZIP, pemeriksaan tautan dan ID melalui shell.

## Status implementasi lokal (2026-08-16)

Tasks 1–7 telah diselesaikan secara lokal pada branch `docs/hifi-agent-workflow`; paket v1.4 telah dibangun dan diaudit. Adopsi ke repository pengguna, push, dan merge masih pending. Checkbox yang belum dicentang dipertahankan sebagai rencana eksekusi dan riwayat pekerjaan, bukan penanda bahwa seluruh langkah tersebut belum dikerjakan atau klaim kemajuan baru yang keliru.

## Global Constraints

- Satu repository dengan branch integrasi `development`; pekerjaan dilakukan pada feature branch.
- Frontend dan backend dipisahkan berdasarkan cakupan tugas.
- Bootstrap tetap menjadi fondasi frontend.
- Warna, tipografi, radius, spacing, border, dan shadow berasal dari design tokens terpusat.
- Low-fidelity menentukan struktur informasi, alur, aksi, state, hierarki, dan batas peran.
- High-fidelity berstatus `hifi-approved` menjadi satu-satunya sumber visual halaman frontend.
- Tiga contoh awal berstatus `visual-direction-approved`, bukan `hifi-approved`.
- Agen tidak membuka browser, mengambil screenshot, membandingkan gambar, atau menjalankan visual regression kecuali diminta eksplisit.
- Review visual dilakukan manual oleh tim; sebelum disetujui statusnya `manual-visual-review-pending`.
- Istilah yang digunakan adalah **murid**, bukan siswa atau peserta didik.
- Jangan memasukkan data nyata murid, rahasia, atau kredensial.
- Entrypoint tidak mengimpor dokumen panjang dan tidak menyuruh agen membaca seluruh `docs/`.
- Keputusan domain yang belum disahkan tetap berada di `docs/decisions/open-validation.md`.

---

## Struktur file target

### Entrypoint dan router

- Modify: `AGENTS.md` — instruksi singkat Codex.
- Modify: `.agents/rules/00-project-context.md` — aturan Always On Antigravity.
- Modify: `docs/ai/00-start-here.md` — router berdasarkan tahap pekerjaan.
- Modify: `docs/ai/context-loading.md` — paket konteks low-fidelity, high-fidelity, frontend, dan backend.
- Modify: `docs/ai/agent-operating-rules.md` — batas keputusan per jenis agen.
- Modify: `docs/ai/task-brief-template.md` — status desain, sumber, dan handoff manual.

### Sumber desain

- Modify: `docs/design/README.md` — indeks dan rantai sumber kebenaran.
- Modify: `docs/design/design-delivery-pipeline.md` — tambah status `blocked-domain-validation`; pertahankan sebagai spesifikasi approved.
- Modify: `docs/design/wireframe-source.md` — tegaskan hanya sumber low-fidelity.
- Modify: `docs/design/wireframe-page-map.md` — pertahankan sebagai peta low-fidelity.
- Create: `docs/design/high-fidelity-source.md` — lokasi, status, dan aturan sumber visual.
- Create: `docs/design/design-page-map.md` — peta gabungan brief, low-fidelity, high-fidelity, ekspor, dan status.
- Create: `docs/design/visual-language.md` — karakter visual approved dan koreksi contoh awal.
- Create: `docs/design/references/README.md` — aturan aset visual direction.
- Copy: `docs/design/references/PG-001-login-visual-direction.png`.
- Copy: `docs/design/references/PG-002-dashboard-visual-direction.png`.
- Copy: `docs/design/references/PG-202-profil-murid-visual-direction.png`.

### Brief per halaman

- Create: `docs/design/pages/README.md` — format dan cara memilih satu brief.
- Create: 26 file `docs/design/pages/PG-xxx-*.md` sesuai inventaris kanonik.

### Frontend dan review

- Modify: `docs/frontend/README.md` — frontend hanya dimulai dari `hifi-approved`.
- Create: `docs/frontend/hifi-to-frontend-workflow.md` — workflow implementasi teknis.
- Modify: `docs/frontend/wireframe-to-ui-workflow.md` — status `superseded` dan tautan pengganti.
- Create: `docs/frontend/manual-visual-review.md` — prosedur review manusia.
- Modify: `docs/frontend/qa-checklist.md` — pisahkan QA agen dan QA visual manual.
- Modify: `docs/frontend/page-implementation-order.md` — tambahkan gerbang desain.
- Modify: `docs/frontend/bootstrap-and-design-tokens.md` — tandai nilai awal sebagai provisional sampai foundations approved.
- Modify: `docs/design/ui-quality-bar.md` — high-fidelity sebagai acuan visual, manusia sebagai reviewer akhir.

### Keputusan, Definition of Done, dan indeks

- Modify: `docs/decisions/decision-log.md` — keputusan pipeline, visual direction, dan review manual.
- Modify: `docs/ux/decision-log.md` — keputusan pemisahan low-fi/high-fi dan istilah visual.
- Modify: `docs/development/definition-of-done.md` — status manual review.
- Modify: `docs/development/workflow.md` — handoff dan branch.
- Modify: `docs/development/documentation-foundation-plan.md` — tandai sebagai riwayat yang disupersede.
- Modify: `docs/README.md` — indeks seluruh sumber baru.

---

### Task 1: Kunci keputusan dan status desain

**Files:**

- Modify: `docs/design/design-delivery-pipeline.md`
- Modify: `docs/decisions/decision-log.md`
- Modify: `docs/ux/decision-log.md`

**Interfaces:**

- Consumes: persetujuan pemilik proyek atas visual direction dan review visual manual.
- Produces: status dan keputusan kanonik yang dipakai seluruh dokumen berikutnya.

- [ ] **Step 1: Pastikan spesifikasi berstatus approved**

Ubah frontmatter menjadi:

```yaml
version: 1.1
status: approved
last_updated: 2026-08-16
```

- [ ] **Step 2: Tambahkan status desain yang dibutuhkan**

Tambahkan definisi berikut pada `design-delivery-pipeline.md`:

```markdown
- `blocked-domain-validation`: brief atau desain tidak dapat disahkan karena keputusan domain belum tersedia;
```

Status ini diletakkan setelah `brief-ready` dan sebelum `lowfi-working`.

- [ ] **Step 3: Tambahkan keputusan proyek**

Tambahkan ke `docs/decisions/decision-log.md`:

```markdown
| DEC-022 | Low-fidelity approved menjadi kontrak UX; high-fidelity approved menjadi kontrak visual frontend. | Dikunci | Frontend tidak lagi mengambil keputusan visual langsung dari wireframe. |
| DEC-023 | Tiga contoh PG-001, PG-002, dan PG-202 menjadi visual direction approved, bukan desain halaman final. | Dikunci | Nilai token dan detail responsif tetap harus disahkan pada foundations serta board high-fidelity Penpot. |
| DEC-024 | Review kesesuaian visual frontend dilakukan manual oleh tim. | Dikunci | Agen menjalankan verifikasi teknis dan berhenti pada status manual-visual-review-pending. |
```

- [ ] **Step 4: Tambahkan keputusan UX**

Tambahkan ke `docs/ux/decision-log.md`:

```markdown
| UX-017 | Pisahkan low-fidelity sebagai kontrak UX dan high-fidelity sebagai kontrak visual. | Mengurangi tafsir visual agen dan menjaga keterlacakan perubahan. | Setiap halaman melewati lowfi-approved lalu hifi-approved sebelum frontend. | Disetujui pemilik proyek |
| UX-018 | Gunakan arah visual tenang, ramah, profesional, biru lembut, navy, putih hangat, krem, soft UI ringan, ikon outline, dan ilustrasi pendukung. | Sesuai karakter layanan BK tanpa terasa menghukum atau generik. | Nilai presisi berasal dari foundations high-fidelity dan design tokens. | Disetujui pemilik proyek |
```

- [ ] **Step 5: Verifikasi keputusan**

Run:

```bash
rg -n "DEC-022|DEC-023|DEC-024|UX-017|UX-018|blocked-domain-validation" docs/
```

Expected: keenam istilah ditemukan pada sumber kanonik yang benar dan tidak muncul sebagai keputusan ganda.

- [ ] **Step 6: Commit**

```bash
git add docs/design/design-delivery-pipeline.md docs/decisions/decision-log.md docs/ux/decision-log.md docs/development/agent-docs-hifi-workflow-plan.md
git commit -m "docs: lock SIBK design delivery decisions"
```

---

### Task 2: Perbarui entrypoint dan pemuatan konteks agen

**Files:**

- Modify: `AGENTS.md`
- Modify: `.agents/rules/00-project-context.md`
- Modify: `docs/ai/00-start-here.md`
- Modify: `docs/ai/context-loading.md`
- Modify: `docs/ai/agent-operating-rules.md`
- Modify: `docs/ai/task-brief-template.md`

**Interfaces:**

- Consumes: status serta sumber kebenaran dari Task 1.
- Produces: router ringkas untuk Codex, Gemini, Sonnet, dan Opus di Antigravity.

- [ ] **Step 1: Revisi `AGENTS.md`**

Pastikan isinya menetapkan:

- tahap aktif saat ini adalah penyelesaian low-fidelity, dilanjutkan high-fidelity, lalu frontend;
- low-fidelity bukan sumber visual frontend;
- frontend hanya dimulai pada `hifi-approved`;
- review visual dilakukan manual;
- agen tidak menggunakan browser/screenshot kecuali diminta eksplisit;
- agent membaca router lalu maksimal empat dokumen domain pada konteks awal.

Target ukuran: maksimal 2.200 karakter.

- [ ] **Step 2: Revisi aturan Always On Antigravity**

Pastikan `.agents/rules/00-project-context.md` memuat aturan yang sama dalam bentuk lebih singkat dan tidak memakai impor `@`.

Target ukuran: maksimal 1.200 karakter.

- [ ] **Step 3: Ubah router berdasarkan tahap**

Pada `docs/ai/00-start-here.md`, buat empat rute terpisah:

```markdown
### UX dan low-fidelity satu halaman
### UI high-fidelity satu halaman
### Frontend satu halaman
### Backend
```

Rute frontend wajib menunjuk `docs/design/pages/PG-xxx-*.md`, `docs/design/design-page-map.md`, `docs/design/high-fidelity-source.md`, `docs/frontend/hifi-to-frontend-workflow.md`, dan aturan token.

- [ ] **Step 4: Revisi anggaran konteks**

Pada `docs/ai/context-loading.md`, tetapkan:

```markdown
- Tugas satu halaman memulai dengan satu brief PG, satu sumber desain sesuai tahap, dan paling banyak dua pedoman tambahan.
- Gambar visual direction tidak dimuat untuk frontend bila board atau ekspor hifi-approved halaman sudah tersedia.
- Browser, screenshot, dan visual regression tidak digunakan otomatis.
```

- [ ] **Step 5: Revisi aturan operasi dan brief tugas**

Tambahkan field berikut pada `docs/ai/task-brief-template.md`:

```markdown
- ID halaman:
- Tahap: lowfi | hifi | frontend | backend
- Status desain saat mulai:
- Board/ekspor sumber:
- State/peran dalam cakupan:
- Pemeriksaan teknis:
- Review visual manual: belum | disetujui | perlu koreksi
```

- [ ] **Step 6: Verifikasi ukuran dan larangan stale**

Run:

```bash
wc -c AGENTS.md .agents/rules/00-project-context.md
rg -n "wireframe menuju UI produksi|frame/ekspor wireframe halaman terkait" AGENTS.md .agents/rules docs/ai
```

Expected: `AGENTS.md` ≤ 2.200 karakter; Always On ≤ 1.200 karakter; pencarian frasa lama tidak menghasilkan output.

- [ ] **Step 7: Commit**

```bash
git add AGENTS.md .agents/rules/00-project-context.md docs/ai
git commit -m "docs: route agents through approved design stages"
```

---

### Task 3: Buat sumber high-fidelity dan visual language

**Files:**

- Create: `docs/design/high-fidelity-source.md`
- Create: `docs/design/design-page-map.md`
- Create: `docs/design/visual-language.md`
- Create: `docs/design/references/README.md`
- Copy: tiga PNG visual direction ke `docs/design/references/`
- Modify: `docs/design/README.md`
- Modify: `docs/design/wireframe-source.md`
- Modify: `docs/design/wireframe-page-map.md`

**Interfaces:**

- Consumes: visual direction dan struktur Penpot yang disetujui.
- Produces: sumber desain yang dapat dirujuk per tahap tanpa mencampur low-fi dan high-fi.

- [ ] **Step 1: Tulis `high-fidelity-source.md`**

Dokumen wajib memuat:

- file Penpot yang sama sebagai sumber kerja;
- Page high-fidelity per modul;
- aturan nama board `PG-* — Nama — Varian`;
- definisi `visual-direction-approved` dan `hifi-approved`;
- paket minimum desktop, tablet, mobile, state, peran, komponen, token, dan aset;
- larangan menebak nilai dari screenshot;
- fallback bila Penpot atau ekspor tidak tersedia.

- [ ] **Step 2: Tulis `visual-language.md`**

Dokumen memuat karakter visual approved, pemetaan tiga contoh, daftar koreksi yang sudah disepakati, serta batas dekorasi dan Soft UI. Dokumen tidak memuat nilai warna perkiraan.

- [ ] **Step 3: Salin tiga referensi visual dengan nama kanonik**

Source:

```text
/workspace/scratch/ffb2fd884e6d/upload/03-Login-UI-1-.png
/workspace/scratch/ffb2fd884e6d/upload/02-Dashboard-Guru-1-.png
/workspace/scratch/ffb2fd884e6d/upload/01-Dashboard-Data-Siswa.png
```

Destination:

```text
docs/design/references/PG-001-login-visual-direction.png
docs/design/references/PG-002-dashboard-visual-direction.png
docs/design/references/PG-202-profil-murid-visual-direction.png
```

- [ ] **Step 4: Buat peta desain gabungan**

`docs/design/design-page-map.md` memiliki satu baris untuk setiap 26 `PG-*` dengan kolom:

```markdown
| PG | Brief | Low-fi board | Low-fi status | Hi-fi board | Hi-fi status | Ekspor |
```

Gunakan `not-started` untuk board high-fidelity yang belum dibuat. Jangan mengarang UUID Penpot.

- [ ] **Step 5: Perjelas sumber low-fidelity**

Tambahkan frontmatter/status pada `wireframe-source.md` dan `wireframe-page-map.md` yang menyatakan keduanya hanya digunakan untuk tahap UX/low-fidelity atau audit maksud UX.

- [ ] **Step 6: Perbarui indeks desain**

Ubah `docs/design/README.md` menjadi router desain dengan urutan:

1. design delivery pipeline;
2. brief halaman;
3. low-fidelity source/map;
4. high-fidelity source/design-page-map;
5. visual language dan quality bar;
6. token serta workflow frontend.

- [ ] **Step 7: Verifikasi aset dan peta**

Run:

```bash
find docs/design/references -maxdepth 1 -type f | sort
rg -o "PG-[0-9]{3}" docs/design/design-page-map.md | sort -u | wc -l
```

Expected: README + tiga PNG tersedia; jumlah ID unik adalah `26`.

- [ ] **Step 8: Commit**

```bash
git add docs/design
git commit -m "docs: add SIBK high-fidelity design sources"
```

---

### Task 4: Buat 26 brief halaman yang hemat konteks

**Files:**

- Create: `docs/design/pages/README.md`
- Create: 26 brief `docs/design/pages/PG-xxx-*.md`

**Interfaces:**

- Consumes: `ui-inventory.md`, `ui-field-actions.md`, `access-matrix.md`, `open-validation.md`, dan design page map.
- Produces: paket konteks terkecil untuk pekerjaan satu halaman.

- [ ] **Step 1: Buat format brief**

Setiap brief menggunakan struktur yang sama:

```markdown
---
page_id: PG-001
design_status: brief-ready
last_updated: 2026-08-16
---

# PG-001 — Login

## Tujuan dan pengguna
## Requirement terkait
## Informasi, field, dan aksi wajib
## State dan variasi peran
## Batas akses dan data sensitif
## Sumber desain
## Keputusan UX
## Validasi domain yang masih terbuka
## Gerbang berikutnya
```

- [ ] **Step 2: Buat daftar 26 file**

Gunakan nama berikut:

```text
PG-001-login.md
PG-002-dashboard.md
PG-003-notifikasi.md
PG-004-akun-saya.md
PG-101-daftar-kasus.md
PG-102-buat-kasus-baru.md
PG-103-detail-kasus.md
PG-104-tindak-lanjut.md
PG-105-catat-konsultasi.md
PG-106-selesaikan-kasus.md
PG-107-koordinasi-waka.md
PG-201-daftar-murid.md
PG-202-profil-murid.md
PG-203-prestasi.md
PG-301-pusat-laporan.md
PG-302-pratinjau-ekspor.md
PG-401-daftar-penugasan.md
PG-402-atur-penugasan.md
PG-403-pengalihan-kasus.md
PG-404-daftar-koreksi.md
PG-405-verifikasi-koreksi.md
PG-406-riwayat-perubahan.md
PG-501-status-sinkronisasi.md
PG-502-rekonsiliasi-identitas.md
PG-503-kelola-akun.md
PG-901-akses-ditolak.md
```

- [ ] **Step 3: Isi setiap brief dari sumber kanonik**

Aturan pengisian:

- tujuan, pengguna, requirement, dan catatan batas berasal dari `ui-inventory.md`;
- field, aksi, validasi, dan state berasal dari `ui-field-actions.md`;
- akses berasal dari `access-matrix.md`;
- hanya ID validasi yang benar-benar terkait dimuat dari `open-validation.md`;
- keputusan UX ditautkan berdasarkan ID, tidak disalin penuh;
- tidak ada data murid nyata;
- maksimal 900 kata per brief.

- [ ] **Step 4: Tetapkan status dengan jujur**

- Gunakan `blocked-domain-validation` bila item open validation mengubah field, alur, status, atau akses halaman.
- Gunakan `brief-ready` bila tidak ada keputusan domain yang menghalangi penyusunan low-fidelity.
- Jangan menggunakan `lowfi-approved` sebelum board Penpot benar-benar direview.

- [ ] **Step 5: Verifikasi cakupan dan ukuran**

Run:

```bash
find docs/design/pages -maxdepth 1 -name 'PG-*.md' | wc -l
rg -l "^page_id: PG-" docs/design/pages | wc -l
find docs/design/pages -name 'PG-*.md' -size +20k -print
```

Expected: dua hitungan pertama `26`; perintah terakhir tidak menghasilkan output.

- [ ] **Step 6: Commit**

```bash
git add docs/design/pages
git commit -m "docs: add selective design briefs for 26 SIBK pages"
```

---

### Task 5: Ganti workflow frontend menjadi high-fidelity ke kode

**Files:**

- Create: `docs/frontend/hifi-to-frontend-workflow.md`
- Create: `docs/frontend/manual-visual-review.md`
- Modify: `docs/frontend/wireframe-to-ui-workflow.md`
- Modify: `docs/frontend/README.md`
- Modify: `docs/frontend/qa-checklist.md`
- Modify: `docs/frontend/page-implementation-order.md`
- Modify: `docs/frontend/bootstrap-and-design-tokens.md`
- Modify: `docs/design/ui-quality-bar.md`

**Interfaces:**

- Consumes: high-fidelity source, brief halaman, visual language, dan status dari Tasks 1–4.
- Produces: workflow implementasi yang dapat dipakai Antigravity tanpa pengujian visual otomatis.

- [ ] **Step 1: Tulis workflow high-fidelity ke frontend**

Tahap wajib:

```text
Audit repository → pilih PG → periksa hifi-approved → petakan komponen/tokens → implementasi → verifikasi teknis → manual-visual-review-pending → review manusia → implemented
```

Dokumen harus melarang implementasi visual bila status halaman belum `hifi-approved`.

- [ ] **Step 2: Tulis prosedur review visual manual**

`manual-visual-review.md` memuat:

- viewport minimum: desktop referensi, tablet `768px`, mobile `390px`;
- state yang diperiksa sesuai brief;
- format koreksi: `PG`, viewport, komponen, gejala, hasil yang diharapkan;
- aturan penggunaan data sintetis;
- pihak yang mengubah status menjadi `implemented`.

- [ ] **Step 3: Supersede workflow lama**

Tambahkan frontmatter pada `wireframe-to-ui-workflow.md`:

```yaml
status: superseded
superseded_by: ../design/design-delivery-pipeline.md
```

Pertahankan isi sebagai riwayat atau ringkas menjadi penunjuk. Dokumen tersebut tidak boleh lagi muncul pada rute frontend aktif.

- [ ] **Step 4: Revisi README dan urutan implementasi**

Pastikan frontend README dan page order mensyaratkan:

- brief tersedia;
- `hifi-approved` tersedia;
- komponen bersama tidak diduplikasi;
- review visual manual setelah pemeriksaan teknis.

- [ ] **Step 5: Pisahkan checklist QA**

Pada `qa-checklist.md`, buat bagian:

```markdown
## Pemeriksaan agen
## Pemeriksaan visual manual
## Persetujuan akhir
```

Agen hanya mencentang pemeriksaan teknis. Reviewer manusia mencentang bagian visual.

- [ ] **Step 6: Tandai token awal sebagai provisional**

Pada `bootstrap-and-design-tokens.md`, pertahankan aturan arsitektur token. Tambahkan status `provisional` pada contoh nilai awal dan pernyataan bahwa nilainya tidak menjadi salinan high-fidelity sampai foundations disahkan.

- [ ] **Step 7: Verifikasi larangan browser otomatis**

Run:

```bash
rg -n -i "wajib.*browser|wajib.*screenshot|visual regression" docs/frontend docs/ai AGENTS.md .agents/rules
```

Expected: tidak ada kewajiban browser/screenshot/visual regression; penyebutan hanya boleh berupa larangan atau penggunaan atas permintaan eksplisit.

- [ ] **Step 8: Commit**

```bash
git add docs/frontend docs/design/ui-quality-bar.md
git commit -m "docs: define hifi-driven frontend workflow"
```

---

### Task 6: Selaraskan Definition of Done, workflow, dan indeks

**Files:**

- Modify: `docs/development/definition-of-done.md`
- Modify: `docs/development/workflow.md`
- Modify: `docs/development/documentation-foundation-plan.md`
- Modify: `docs/README.md`

**Interfaces:**

- Consumes: seluruh sumber dan workflow aktif.
- Produces: navigasi dokumentasi serta gerbang merge yang konsisten.

- [ ] **Step 1: Revisi Definition of Done**

Tambahkan syarat:

- status desain awal `hifi-approved`;
- pemeriksaan teknis agen lulus;
- review visual manual tercatat;
- status akhir `implemented`;
- data screenshot/manual review bersifat sintetis.

- [ ] **Step 2: Revisi workflow pengembangan**

Tetapkan bahwa agen boleh menyerahkan pekerjaan pada `manual-visual-review-pending`, tetapi tidak boleh menyebut halaman selesai secara visual sebelum persetujuan manusia.

- [ ] **Step 3: Tandai plan lama sebagai riwayat**

Tambahkan catatan pada `documentation-foundation-plan.md` bahwa dokumen tersebut merekam baseline v1.3 dan telah digantikan oleh `agent-docs-hifi-workflow-plan.md` untuk perubahan desain berikutnya.

- [ ] **Step 4: Perbarui indeks utama**

Tambahkan ke `docs/README.md`:

- design delivery pipeline;
- high-fidelity source;
- design page map;
- visual language;
- page briefs;
- hifi-to-frontend workflow;
- manual visual review.

- [ ] **Step 5: Verifikasi rute aktif**

Run:

```bash
rg -n "wireframe-to-ui-workflow" AGENTS.md .agents/rules docs/README.md docs/ai docs/frontend/README.md
```

Expected: tidak ada rute aktif yang menjadikan workflow lama sebagai sumber implementasi frontend.

- [ ] **Step 6: Commit**

```bash
git add docs/development docs/README.md
git commit -m "docs: align SIBK delivery gates and indexes"
```

---

### Task 7: Audit konsistensi dan buat paket v1.4

**Files:**

- Verify: seluruh `outputs/sibk-agent-docs/`
- Create: `outputs/SIBK_Agent_Docs_v1.4.zip`

**Interfaces:**

- Consumes: hasil Tasks 1–6.
- Produces: paket dokumentasi siap dipindahkan ke repository pengguna.

- [ ] **Step 1: Periksa 26 ID halaman**

Run dari root paket:

```bash
for id in $(rg -o "PG-[0-9]{3}" docs/product/ui-inventory.md | sort -u); do
  test -n "$(find docs/design/pages -name "$id-*.md" -print -quit)" || echo "MISSING $id"
done
```

Expected: tidak ada output.

- [ ] **Step 2: Periksa istilah yang dilarang**

Run:

```bash
rg -n -i "\bsiswa\b|peserta didik|SMA NEGERI 1|riwayeat" AGENTS.md .agents docs -g '*.md'
```

Expected: tidak ada output kecuali kutipan historis yang diberi label jelas; preferensi adalah nol output.

- [ ] **Step 3: Periksa referensi stale**

Run:

```bash
rg -n "Tahap aktif: frontend, dari wireframe menuju UI produksi|frame/ekspor melalui `docs/design/wireframe-page-map.md`" AGENTS.md .agents docs/ai docs/frontend
```

Expected: tidak ada output.

- [ ] **Step 4: Periksa tautan relatif Markdown**

Run dari root paket:

```bash
python - <<'PY'
from pathlib import Path
import re

root = Path.cwd()
missing = []
pattern = re.compile(r"\[[^\]]+\]\(([^)]+)\)")

for source in root.rglob("*.md"):
    text = source.read_text(encoding="utf-8")
    for target in pattern.findall(text):
        if target.startswith(("http://", "https://", "#", "mailto:")):
            continue
        path = target.split("#", 1)[0]
        if not path:
            continue
        resolved = (source.parent / path).resolve()
        if not resolved.exists():
            missing.append(f"{source.relative_to(root)} -> {target}")

if missing:
    print("\n".join(missing))
    raise SystemExit(1)

print("All relative Markdown links resolve.")
PY
```

Expected: `All relative Markdown links resolve.`

- [ ] **Step 5: Periksa batas konteks entrypoint**

Run:

```bash
wc -c AGENTS.md .agents/rules/00-project-context.md docs/ai/00-start-here.md
```

Expected:

- `AGENTS.md` ≤ 2.200 karakter;
- Always On ≤ 1.200 karakter;
- router ≤ 4.000 karakter.

- [ ] **Step 6: Buat ZIP reproducible**

Run dari `outputs/`:

```bash
zip -r -X SIBK_Agent_Docs_v1.4.zip sibk-agent-docs
unzip -t SIBK_Agent_Docs_v1.4.zip
```

Expected: `No errors detected in compressed data`.

- [ ] **Step 7: Catat ringkasan handoff**

Ringkasan harus menyebutkan:

- jumlah Markdown dan aset;
- 26 brief tersedia;
- low-fi dan high-fi terpisah;
- browser/screenshot tidak otomatis;
- visual review manual;
- GitHub belum diubah;
- nama branch dan commit yang disarankan.

- [ ] **Step 8: Commit saat berada di repository pengguna**

```bash
git add AGENTS.md .agents docs
git commit -m "docs: adopt high-fidelity SIBK agent workflow"
```

Jangan commit ZIP ke repository kecuali tim memang menyimpan paket distribusi. ZIP terutama digunakan untuk pemindahan manual ke repository.
