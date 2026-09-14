# Checkpoint 1 Fondasi Arsip Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membuat repository private `Aflahul/sibk-docs-archive`, menyalin dokumen historis sebagai snapshot bersih, dan memverifikasi salinan sebelum ada dokumen yang dihapus dari `sibk`.

**Architecture:** Dokumen disalin lebih dahulu ke clone sementara di `.worktrees/sibk-docs-archive`, lalu di-commit dan di-push sebagai satu snapshot. Clone yang sudah terverifikasi dipindahkan ke folder saudara `sibk-docs-archive`; repository utama hanya menerima catatan checkpoint dan belum menghapus dokumen lama pada tahap ini.

**Tech Stack:** Git, GitHub CLI, PowerShell, Markdown, SHA-256.

**Spec:** `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`

## Global Constraints

- Baseline pengembangan aktif adalah `cobasidebar`; pekerjaan checkpoint
  dilakukan pada branch `checkpoint-1-arsip`; `main` tidak disentuh.
- Repository arsip wajib private dan berada di `Aflahul/sibk-docs-archive`.
- Arsip memakai snapshot bersih dengan satu initial commit.
- Perangkat penelitian RBAC tidak dimasukkan ke arsip.
- Tidak ada dokumen yang dihapus dari `sibk` pada Checkpoint 1.
- Jangan memakai force push, menulis ulang histori, atau menghapus branch.
- Jangan menyalin `.env`, credential, cache, hasil build, atau file data lokal.
- Gunakan Bahasa Indonesia yang mudah dipahami pada commit dan dokumentasi.
- Gunakan path literal dan verifikasi target sebelum memindahkan clone di Windows.
- Bila remote, hash, atau undangan collaborator gagal diverifikasi, checkpoint tetap aktif dan repository sumber tidak diubah.
- `docs/current-work.md` dibuat sebelum tindakan eksternal dan diperbarui setelah
  setiap task.
- Setelah satu task selesai, centang seluruh step task tersebut, commit catatan
  progres, lalu push branch `checkpoint-1-arsip` agar pengembang lain dapat
  melanjutkan dari remote.

---

### Task 0: Buat catatan handoff sebelum tindakan eksternal

**Files:**
- Create: `docs/current-work.md`

**Interfaces:**
- Consumes: worktree bersih pada branch `checkpoint-1-arsip`.
- Produces: satu petunjuk aktif yang dapat dibaca pengembang pengganti.

- [x] **Step 1: Pastikan worktree implementasi benar**

Run:

```powershell
git branch --show-current
git status --short
git merge-base --is-ancestor 4b2d370 HEAD
```

Expected: branch adalah `checkpoint-1-arsip`, status kosong, dan pemeriksaan
ancestor exit code 0.

- [x] **Step 2: Buat catatan pekerjaan aktif**

Buat `docs/current-work.md` dengan isi:

```markdown
# Pekerjaan Aktif Ruang BK

## Status

- Checkpoint: 1 — Fondasi arsip
- Status checkpoint: Sedang dikerjakan
- Branch kerja: `checkpoint-1-arsip`
- Baseline pengembangan: `cobasidebar`
- Task terakhir selesai: Belum ada
- Task berikutnya: Task 1 — Verifikasi keadaan awal dan sumber arsip
- Commit acuan plan: `4b2d370`

## Keadaan eksternal

- Repository `Aflahul/sibk-docs-archive`: Belum dibuat
- Snapshot arsip: Belum disusun
- Undangan `ibnuahmadfauzi`: Belum dikirim
- Clone akhir di luar proyek: Belum tersedia

## Verifikasi terakhir

- Branch kerja dan status bersih sudah diperiksa.
- Belum ada tindakan eksternal.

## Cara melanjutkan

1. Baca `AGENTS.md`.
2. Baca file ini.
3. Baca plan Checkpoint 1 mulai dari Task 1.
4. Jalankan `git status` dan `git log -1 --oneline` sebelum melanjutkan.
5. Jangan mengulang tindakan eksternal tanpa memeriksa keadaannya lebih dahulu.

## Acuan

- Spec: `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`
- Plan: `docs/superpowers/plans/2026-09-14-checkpoint-1-fondasi-arsip.md`
```

- [x] **Step 3: Commit dan bagikan titik awal handoff**

Run:

```powershell
git add docs/current-work.md docs/superpowers/plans/2026-09-14-checkpoint-1-fondasi-arsip.md
git commit -m "docs: mulai tahap pertama pengarsipan"
git push -u origin checkpoint-1-arsip
```

Expected: branch `checkpoint-1-arsip` tersedia pada remote dan worktree kembali
bersih.

---

### Task 1: Verifikasi keadaan awal dan sumber arsip

**Files:**
- Read: `docs/requirements/PRD_Aplikasi_BK_v1.0.docx`
- Read: `docs/requirements/PRD_Aplikasi_BK_v1.0.md`
- Read: `docs/requirements/SRS_Aplikasi_BK_v1.0.docx`
- Read: `docs/requirements/SRS_Aplikasi_BK_v1.0.md`
- Read: `docs/superpowers/plans/2026-08-23-konfigurasi-koneksi-integrasi.md`
- Read: `docs/superpowers/plans/2026-09-12-penyempurnaan-alur-operasional-bk.md`
- Read: `docs/superpowers/plans/2026-09-13-portal-waka-berbasis-tujuan.md`
- Read: `docs/superpowers/specs/2026-09-13-portal-waka-wireframe-design.md`
- Read: `docs/testing/2026-09-13-uat-portal-waka.md`
- Read: `docs/development-log.md`

**Interfaces:**
- Consumes: HEAD `cobasidebar` yang bersih dan autentikasi GitHub akun `Aflahul`.
- Produces: daftar sepuluh file sumber yang ada, SHA sumber `b4310e1`, serta hash SHA-256 pembanding.

- [x] **Step 1: Pastikan branch dan worktree benar**

Run:

```powershell
git branch --show-current
git status --short
git merge-base --is-ancestor b4310e1 HEAD
git log -4 --oneline
```

Expected: branch adalah `checkpoint-1-arsip`, status kosong, pemeriksaan ancestor exit
code 0, dan riwayat memuat commit `b4310e1 docs: rapikan catatan dan rencana
pengembangan`.

- [x] **Step 2: Pastikan autentikasi GitHub aktif**

Run:

```powershell
gh auth status
gh api user --jq "{login: .login, name: .name}"
```

Expected: akun aktif adalah `Aflahul` dan token mempunyai scope `repo`.

- [x] **Step 3: Pastikan seluruh sumber arsip tersedia**

Run:

```powershell
$archiveSources = @(
    'docs/requirements/PRD_Aplikasi_BK_v1.0.docx',
    'docs/requirements/PRD_Aplikasi_BK_v1.0.md',
    'docs/requirements/SRS_Aplikasi_BK_v1.0.docx',
    'docs/requirements/SRS_Aplikasi_BK_v1.0.md',
    'docs/superpowers/plans/2026-08-23-konfigurasi-koneksi-integrasi.md',
    'docs/superpowers/plans/2026-09-12-penyempurnaan-alur-operasional-bk.md',
    'docs/superpowers/plans/2026-09-13-portal-waka-berbasis-tujuan.md',
    'docs/superpowers/specs/2026-09-13-portal-waka-wireframe-design.md',
    'docs/testing/2026-09-13-uat-portal-waka.md',
    'docs/development-log.md'
)
$missingSources = $archiveSources | Where-Object { -not (Test-Path -LiteralPath $_) }
if ($missingSources.Count -gt 0) { throw "Sumber arsip tidak ditemukan: $($missingSources -join ', ')" }
$archiveSources | ForEach-Object { Get-FileHash -Algorithm SHA256 -LiteralPath $_ }
```

Expected: tidak ada exception dan tepat sepuluh hash ditampilkan.

- [x] **Step 4: Pastikan remote belum dipakai**

Run:

```powershell
gh repo view Aflahul/sibk-docs-archive --json nameWithOwner,isPrivate
```

Expected: command menyatakan repository belum ditemukan. Bila repository sudah
ada, hentikan task dan periksa isinya; jangan menimpa remote.

- [x] **Step 5: Catat hasil Task 1 untuk handoff**

Update `docs/current-work.md`:

```text
Task terakhir selesai: Task 1 — Verifikasi keadaan awal dan sumber arsip
Task berikutnya: Task 2 — Buat repository private dan clone sementara
Repository arsip: Belum dibuat
Verifikasi terakhir: Sepuluh sumber arsip tersedia; remote belum digunakan.
```

Centang Step 1 sampai Step 5 pada Task 1, lalu jalankan:

```powershell
git add docs/current-work.md docs/superpowers/plans/2026-09-14-checkpoint-1-fondasi-arsip.md
git commit -m "docs: catat pemeriksaan sumber arsip"
git push
```

Expected: commit dan push berhasil; status worktree bersih.

---

### Task 2: Buat repository private dan clone sementara

**Files:**
- Create remotely: `Aflahul/sibk-docs-archive`
- Create temporarily: `.worktrees/sibk-docs-archive/`

**Interfaces:**
- Consumes: autentikasi akun `Aflahul` dan hasil Task 1.
- Produces: remote private kosong dan clone lokal sementara yang dapat ditulis.

- [x] **Step 1: Pastikan lokasi clone sementara aman**

Run:

```powershell
$workspacePath = (Resolve-Path -LiteralPath '.').Path
$temporaryArchivePath = Join-Path $workspacePath '.worktrees\sibk-docs-archive'
if ((Split-Path -Leaf $temporaryArchivePath) -ne 'sibk-docs-archive') {
    throw 'Nama folder clone sementara tidak aman.'
}
if (Test-Path -LiteralPath $temporaryArchivePath) {
    throw 'Folder clone sementara sudah ada; periksa secara manual sebelum melanjutkan.'
}
$temporaryArchivePath
```

Expected: target berakhir dengan `.worktrees\sibk-docs-archive` dan belum ada.

- [x] **Step 2: Buat remote private**

Run:

```powershell
gh repo create Aflahul/sibk-docs-archive --private --description "Arsip dokumentasi historis proyek Ruang BK"
```

Expected: repository berhasil dibuat dan URL GitHub ditampilkan.

- [x] **Step 3: Verifikasi visibility sebelum menyalin dokumen**

Run:

```powershell
gh repo view Aflahul/sibk-docs-archive --json nameWithOwner,isPrivate,url
```

Expected: `nameWithOwner` adalah `Aflahul/sibk-docs-archive` dan `isPrivate`
bernilai `true`. Bila false, hentikan checkpoint tanpa menyalin dokumen.

- [x] **Step 4: Clone remote ke lokasi sementara**

Run:

```powershell
git clone https://github.com/Aflahul/sibk-docs-archive.git .worktrees/sibk-docs-archive
```

Expected: clone berhasil dan `.worktrees/sibk-docs-archive/.git` tersedia.

- [x] **Step 5: Catat hasil Task 2 untuk handoff**

Update `docs/current-work.md`:

```text
Task terakhir selesai: Task 2 — Buat repository private dan clone sementara
Task berikutnya: Task 3 — Susun snapshot arsip
Repository arsip: Sudah dibuat dan terverifikasi private
Verifikasi terakhir: Remote private tersedia dan clone sementara berhasil dibuat.
```

Centang Step 1 sampai Step 5 pada Task 2, lalu jalankan:

```powershell
git add docs/current-work.md docs/superpowers/plans/2026-09-14-checkpoint-1-fondasi-arsip.md
git commit -m "docs: catat repository arsip sudah dibuat"
git push
```

Expected: commit dan push berhasil. Clone arsip tetap tidak ikut status
repository `sibk`.

---

### Task 3: Susun snapshot arsip

**Files:**
- Create: `.worktrees/sibk-docs-archive/README.md`
- Create: `.worktrees/sibk-docs-archive/archive-index.md`
- Copy: `.worktrees/sibk-docs-archive/requirements/v1.0/*`
- Copy: `.worktrees/sibk-docs-archive/plans/completed/*`
- Copy: `.worktrees/sibk-docs-archive/plans/superseded/*`
- Copy: `.worktrees/sibk-docs-archive/specs/completed/*`
- Copy: `.worktrees/sibk-docs-archive/testing/completed/*`

**Interfaces:**
- Consumes: sepuluh sumber terverifikasi dari Task 1.
- Produces: snapshot tanpa perangkat penelitian, secret, dan hasil build.

- [ ] **Step 1: Buat struktur tujuan**

Run:

```powershell
$archiveRoot = (Resolve-Path -LiteralPath '.worktrees\sibk-docs-archive').Path
@(
    'requirements\v1.0',
    'plans\completed',
    'plans\superseded',
    'specs\completed',
    'testing\completed'
) | ForEach-Object { New-Item -ItemType Directory -Force -Path (Join-Path $archiveRoot $_) | Out-Null }
```

Expected: lima folder tujuan tersedia di clone arsip.

- [ ] **Step 2: Salin dokumen ke kategori yang benar**

Run:

```powershell
$archiveRoot = (Resolve-Path -LiteralPath '.worktrees\sibk-docs-archive').Path
Copy-Item -LiteralPath 'docs\requirements\PRD_Aplikasi_BK_v1.0.docx' -Destination (Join-Path $archiveRoot 'requirements\v1.0\PRD_Aplikasi_BK_v1.0.docx')
Copy-Item -LiteralPath 'docs\requirements\PRD_Aplikasi_BK_v1.0.md' -Destination (Join-Path $archiveRoot 'requirements\v1.0\PRD_Aplikasi_BK_v1.0.md')
Copy-Item -LiteralPath 'docs\requirements\SRS_Aplikasi_BK_v1.0.docx' -Destination (Join-Path $archiveRoot 'requirements\v1.0\SRS_Aplikasi_BK_v1.0.docx')
Copy-Item -LiteralPath 'docs\requirements\SRS_Aplikasi_BK_v1.0.md' -Destination (Join-Path $archiveRoot 'requirements\v1.0\SRS_Aplikasi_BK_v1.0.md')
Copy-Item -LiteralPath 'docs\superpowers\plans\2026-08-23-konfigurasi-koneksi-integrasi.md' -Destination (Join-Path $archiveRoot 'plans\completed\2026-08-23-konfigurasi-koneksi-integrasi.md')
Copy-Item -LiteralPath 'docs\superpowers\plans\2026-09-13-portal-waka-berbasis-tujuan.md' -Destination (Join-Path $archiveRoot 'plans\completed\2026-09-13-portal-waka-berbasis-tujuan.md')
Copy-Item -LiteralPath 'docs\superpowers\plans\2026-09-12-penyempurnaan-alur-operasional-bk.md' -Destination (Join-Path $archiveRoot 'plans\superseded\2026-09-12-penyempurnaan-alur-operasional-bk.md')
Copy-Item -LiteralPath 'docs\superpowers\specs\2026-09-13-portal-waka-wireframe-design.md' -Destination (Join-Path $archiveRoot 'specs\completed\2026-09-13-portal-waka-wireframe-design.md')
Copy-Item -LiteralPath 'docs\testing\2026-09-13-uat-portal-waka.md' -Destination (Join-Path $archiveRoot 'testing\completed\2026-09-13-uat-portal-waka.md')
Copy-Item -LiteralPath 'docs\development-log.md' -Destination (Join-Path $archiveRoot 'testing\completed\development-log-through-2026-09-14.md')
```

Expected: sepuluh file hasil salinan tersedia.

- [ ] **Step 3: Buat README arsip**

Buat `.worktrees/sibk-docs-archive/README.md` dengan isi:

```markdown
# Arsip Dokumentasi Ruang BK

Repository private ini menyimpan snapshot dokumen historis proyek Ruang BK
yang tidak lagi diperlukan pada repository aplikasi aktif.

- Repository sumber: `ibnuahmadfauzi/sibk`
- Branch sumber: `cobasidebar`
- Commit sumber: `b4310e1`
- Tanggal snapshot: 14 September 2026

Dokumen di sini hanya untuk penelusuran histori. Perilaku aplikasi aktif tetap
mengacu pada PRD/SRS, API contract, dan plan aktif di repository `sibk`.

Perangkat penelitian RBAC, screenshot, credential, `.env`, cache, dan hasil
build tidak disimpan dalam arsip ini.
```

- [ ] **Step 4: Buat indeks arsip**

Buat `.worktrees/sibk-docs-archive/archive-index.md` dengan isi:

```markdown
# Indeks Arsip

| Kategori | Dokumen | Status | Path asal |
|---|---|---|---|
| Requirement | PRD v1.0 DOCX dan Markdown | Digantikan v1.1 | `docs/requirements/` |
| Requirement | SRS v1.0 DOCX dan Markdown | Digantikan v1.1 | `docs/requirements/` |
| Plan | Konfigurasi koneksi integrasi | Selesai | `docs/superpowers/plans/` |
| Plan | Portal Waka berbasis tujuan | Selesai | `docs/superpowers/plans/` |
| Plan | Penyempurnaan alur operasional BK | Digantikan | `docs/superpowers/plans/` |
| Spec | Portal Waka wireframe | Selesai | `docs/superpowers/specs/` |
| Pengujian | UAT Portal Waka | Selesai | `docs/testing/` |
| Riwayat | Development log sampai 14 September 2026 | Snapshot | `docs/development-log.md` |

Snapshot berasal dari branch `cobasidebar` pada commit `b4310e1`.
```

- [ ] **Step 5: Pastikan perangkat penelitian tidak ikut**

Run:

```powershell
rg -n -i "RBAC-Test-Results|bukti-rbac|rbac-results-analysis|rbac:scenario" .worktrees/sibk-docs-archive
```

Expected: tidak ada hasil.

- [ ] **Step 6: Catat hasil Task 3 untuk handoff**

Update `docs/current-work.md`:

```text
Task terakhir selesai: Task 3 — Susun snapshot arsip
Task berikutnya: Task 4 — Verifikasi, commit, dan push snapshot
Snapshot arsip: Sudah disusun pada clone sementara
Verifikasi terakhir: Sepuluh file tersalin dan perangkat penelitian tidak ikut.
```

Centang Step 1 sampai Step 6 pada Task 3, lalu jalankan:

```powershell
git add docs/current-work.md docs/superpowers/plans/2026-09-14-checkpoint-1-fondasi-arsip.md
git commit -m "docs: catat snapshot arsip sudah disusun"
git push
```

Expected: commit dan push berhasil; snapshot masih tersedia pada clone arsip.

---

### Task 4: Verifikasi, commit, dan push snapshot

**Files:**
- Verify: `.worktrees/sibk-docs-archive/**`

**Interfaces:**
- Consumes: snapshot dari Task 3.
- Produces: initial commit remote yang identik dengan sumber terpilih.

- [ ] **Step 1: Bandingkan hash sumber dan salinan**

Run:

```powershell
$archiveRoot = (Resolve-Path -LiteralPath '.worktrees\sibk-docs-archive').Path
$hashPairs = @(
    @('docs\requirements\PRD_Aplikasi_BK_v1.0.docx', 'requirements\v1.0\PRD_Aplikasi_BK_v1.0.docx'),
    @('docs\requirements\PRD_Aplikasi_BK_v1.0.md', 'requirements\v1.0\PRD_Aplikasi_BK_v1.0.md'),
    @('docs\requirements\SRS_Aplikasi_BK_v1.0.docx', 'requirements\v1.0\SRS_Aplikasi_BK_v1.0.docx'),
    @('docs\requirements\SRS_Aplikasi_BK_v1.0.md', 'requirements\v1.0\SRS_Aplikasi_BK_v1.0.md'),
    @('docs\superpowers\plans\2026-08-23-konfigurasi-koneksi-integrasi.md', 'plans\completed\2026-08-23-konfigurasi-koneksi-integrasi.md'),
    @('docs\superpowers\plans\2026-09-13-portal-waka-berbasis-tujuan.md', 'plans\completed\2026-09-13-portal-waka-berbasis-tujuan.md'),
    @('docs\superpowers\plans\2026-09-12-penyempurnaan-alur-operasional-bk.md', 'plans\superseded\2026-09-12-penyempurnaan-alur-operasional-bk.md'),
    @('docs\superpowers\specs\2026-09-13-portal-waka-wireframe-design.md', 'specs\completed\2026-09-13-portal-waka-wireframe-design.md'),
    @('docs\testing\2026-09-13-uat-portal-waka.md', 'testing\completed\2026-09-13-uat-portal-waka.md'),
    @('docs\development-log.md', 'testing\completed\development-log-through-2026-09-14.md')
)
foreach ($pair in $hashPairs) {
    $sourceHash = (Get-FileHash -Algorithm SHA256 -LiteralPath $pair[0]).Hash
    $archiveHash = (Get-FileHash -Algorithm SHA256 -LiteralPath (Join-Path $archiveRoot $pair[1])).Hash
    if ($sourceHash -ne $archiveHash) { throw "Hash berbeda untuk $($pair[0])" }
}
"Semua $($hashPairs.Count) salinan sesuai."
```

Expected: `Semua 10 salinan sesuai.`

- [ ] **Step 2: Periksa isi commit**

Run:

```powershell
git -C .worktrees/sibk-docs-archive status --short
git -C .worktrees/sibk-docs-archive diff --check
```

Expected: hanya file arsip yang direncanakan tampil dan diff-check bersih.

- [ ] **Step 3: Buat initial commit**

Run:

```powershell
git -C .worktrees/sibk-docs-archive add README.md archive-index.md requirements plans specs testing
git -C .worktrees/sibk-docs-archive commit -m "docs: simpan dokumen lama Ruang BK"
```

Expected: satu root commit berhasil dibuat.

- [ ] **Step 4: Push snapshot**

Run:

```powershell
git -C .worktrees/sibk-docs-archive push -u origin main
```

Expected: branch `main` tersedia pada remote private.

- [ ] **Step 5: Verifikasi remote setelah push**

Run:

```powershell
gh repo view Aflahul/sibk-docs-archive --json nameWithOwner,isPrivate,defaultBranchRef,url
gh api repos/Aflahul/sibk-docs-archive/contents --jq ".[].name"
```

Expected: repository tetap private, default branch `main`, dan root berisi
`README.md`, `archive-index.md`, `requirements`, `plans`, `specs`, serta
`testing`.

- [ ] **Step 6: Catat hasil Task 4 untuk handoff**

Update `docs/current-work.md`:

```text
Task terakhir selesai: Task 4 — Verifikasi, commit, dan push snapshot
Task berikutnya: Task 5 — Undang collaborator dan pindahkan clone keluar proyek
Snapshot arsip: Sudah di-commit, di-push, dan diverifikasi
Verifikasi terakhir: Sepuluh hash sesuai dan isi remote private lengkap.
```

Centang Step 1 sampai Step 6 pada Task 4, lalu jalankan:

```powershell
git add docs/current-work.md docs/superpowers/plans/2026-09-14-checkpoint-1-fondasi-arsip.md
git commit -m "docs: catat snapshot arsip sudah disimpan"
git push
```

Expected: commit dan push berhasil.

---

### Task 5: Undang collaborator dan pindahkan clone keluar proyek

**Files:**
- Move: `.worktrees/sibk-docs-archive/`
- To: `D:\PPG 2026\SEMESTER 2\sibk-docs-archive\`

**Interfaces:**
- Consumes: remote private terverifikasi dari Task 4.
- Produces: undangan collaborator dan clone lokal di luar folder `sibk`.

- [ ] **Step 1: Kirim undangan collaborator**

Run:

```powershell
gh api --method PUT repos/Aflahul/sibk-docs-archive/collaborators/ibnuahmadfauzi -f permission=push
```

Expected: GitHub mengembalikan undangan atau status sukses. Penerimaan undangan
oleh `ibnuahmadfauzi` boleh tetap menunggu dan dicatat pada `current-work.md`.

- [ ] **Step 2: Verifikasi lokasi sumber dan tujuan move**

Run:

```powershell
$workspacePath = (Resolve-Path -LiteralPath '.').Path
$temporaryArchivePath = (Resolve-Path -LiteralPath '.worktrees\sibk-docs-archive').Path
$finalArchivePath = 'D:\PPG 2026\SEMESTER 2\sibk-docs-archive'
if (-not $temporaryArchivePath.StartsWith((Join-Path $workspacePath '.worktrees'), [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'Sumber move berada di luar folder sementara yang diizinkan.'
}
if ((Split-Path -Leaf $finalArchivePath) -ne 'sibk-docs-archive') {
    throw 'Target move tidak aman.'
}
if (Test-Path -LiteralPath $finalArchivePath) {
    throw 'Folder arsip tujuan sudah ada; jangan menimpanya.'
}
```

Expected: tidak ada exception.

- [ ] **Step 3: Pindahkan clone ke folder saudara**

Run:

```powershell
$workspacePath = (Resolve-Path -LiteralPath '.').Path
$temporaryArchivePath = (Resolve-Path -LiteralPath '.worktrees\sibk-docs-archive').Path
$finalArchivePath = 'D:\PPG 2026\SEMESTER 2\sibk-docs-archive'
Move-Item -LiteralPath $temporaryArchivePath -Destination $finalArchivePath
```

Expected: `D:\PPG 2026\SEMESTER 2\sibk-docs-archive\.git` tersedia dan
`.worktrees\sibk-docs-archive` tidak lagi ada.

- [ ] **Step 4: Verifikasi clone akhir**

Run:

```powershell
git -C "D:\PPG 2026\SEMESTER 2\sibk-docs-archive" status --short --branch
git -C "D:\PPG 2026\SEMESTER 2\sibk-docs-archive" remote -v
```

Expected: branch `main` bersih dan remote menunjuk
`Aflahul/sibk-docs-archive`.

- [ ] **Step 5: Catat hasil Task 5 untuk handoff**

Update `docs/current-work.md`:

```text
Task terakhir selesai: Task 5 — Undang collaborator dan pindahkan clone keluar proyek
Task berikutnya: Task 6 — Tutup Checkpoint 1 pada repository aktif
Undangan ibnuahmadfauzi: Sudah dikirim; penerimaan mengikuti status GitHub
Clone akhir di luar proyek: D:\PPG 2026\SEMESTER 2\sibk-docs-archive
Verifikasi terakhir: Clone akhir bersih dan remote menunjuk repository arsip.
```

Centang Step 1 sampai Step 5 pada Task 5, lalu jalankan:

```powershell
git add docs/current-work.md docs/superpowers/plans/2026-09-14-checkpoint-1-fondasi-arsip.md
git commit -m "docs: catat akses dan lokasi repository arsip"
git push
```

Expected: commit dan push berhasil.

---

### Task 6: Tutup Checkpoint 1 pada repository aktif

**Files:**
- Modify: `docs/current-work.md`

**Interfaces:**
- Consumes: hasil verifikasi remote, lokasi clone akhir, dan status undangan.
- Produces: petunjuk pendek untuk memulai Checkpoint 2.

- [ ] **Step 1: Perbarui catatan pekerjaan aktif**

Ganti bagian status di `docs/current-work.md` dengan isi:

```markdown
# Pekerjaan Aktif Ruang BK

## Status

- Checkpoint selesai: 1 — Fondasi arsip
- Checkpoint aktif berikutnya: 2 — Bersihkan baseline `cobasidebar`
- Branch pengembangan: `cobasidebar`
- Repository arsip: `https://github.com/Aflahul/sibk-docs-archive`
- Commit sumber snapshot: `b4310e1`
- Lokasi clone arsip: `D:\PPG 2026\SEMESTER 2\sibk-docs-archive`

## Hasil terakhir

- Repository arsip dibuat private.
- Sepuluh dokumen historis disalin dan hash-nya sesuai.
- Perangkat penelitian RBAC tidak dimasukkan ke arsip.
- Dokumen sumber di `sibk` belum dihapus.
- Undangan collaborator sudah dikirim; status penerimaan diperiksa kembali pada
  Checkpoint 2.

## Langkah berikutnya

1. Tulis plan rinci Checkpoint 2 dari spec aktif.
2. Verifikasi kembali remote arsip.
3. Perbarui README, AGENTS, versi PHP, dan tautan dokumentasi.
4. Hapus dokumen yang sudah diarsipkan dari HEAD `cobasidebar`.

## Acuan

- Spec: `docs/superpowers/specs/2026-09-14-baseline-cobasidebar-arsip-dan-penyederhanaan-design.md`
- Plan selesai: `docs/superpowers/plans/2026-09-14-checkpoint-1-fondasi-arsip.md`
```

Jika undangan collaborator belum diterima, tambahkan satu butir di bagian
`Hasil terakhir`: `Undangan collaborator sudah dikirim dan masih menunggu
penerimaan.`

- [ ] **Step 2: Jalankan gate akhir Checkpoint 1**

Run:

```powershell
gh repo view Aflahul/sibk-docs-archive --json nameWithOwner,isPrivate,defaultBranchRef
git -C "D:\PPG 2026\SEMESTER 2\sibk-docs-archive" status --short --branch
git status --short
git diff --check
```

Expected: remote private, clone arsip bersih, dan repository `sibk` hanya
menampilkan perubahan `docs/current-work.md` serta checkbox plan.

- [ ] **Step 3: Commit plan dan catatan checkpoint**

Run:

```powershell
git add docs/current-work.md docs/superpowers/plans/2026-09-14-checkpoint-1-fondasi-arsip.md
git commit -m "docs: selesaikan tahap pertama pengarsipan"
git push
```

Expected: commit berhasil dan tidak memuat penghapusan dokumen lama.

- [ ] **Step 4: Pastikan checkpoint berhenti sebelum pembersihan**

Run:

```powershell
git status --short
Test-Path -LiteralPath 'docs\requirements\PRD_Aplikasi_BK_v1.0.docx'
Test-Path -LiteralPath 'docs\superpowers\plans\2026-08-23-konfigurasi-koneksi-integrasi.md'
```

Expected: status bersih dan kedua pemeriksaan menghasilkan `True`. Pembersihan
baru dilakukan melalui plan Checkpoint 2.
