# SIBK / Ruang BK

Aplikasi layanan Bimbingan dan Konseling SMK Negeri 1 Surabaya.

## Workflow

- Awali dengan `git status --short --branch` dan `git log -5 --oneline`.
- Pertahankan perubahan milik pengguna.
- Ikuti urutan context: `AGENTS.md` -> active plan -> targeted canonical
  requirement -> source code.
- Tentukan pekerjaan dari instruksi pengguna, branch/worktree, dan active plan.
- Baca hanya source, test, requirement, dan kontrak yang diperlukan oleh task
  aktif; jangan membaca historical docs kecuali memang diperlukan.
- Gunakan Git commit sebagai checkpoint dan histori.
- `cobasidebar` adalah baseline pengembangan; `main` versi stabil produksi.
- Kerjakan di feature branch/worktree, PR ke `cobasidebar`; jangan force push atau menghapus branch yang belum di-merge.

## Documentation

Canonical:
- PRD: keputusan/scope produk.
- SRS: system behavior, business rule, authorization, invariant, acceptance criteria, requirement ID.
- `docs/api-contract.md`: interface teknis, endpoint, request/response, controller/service contract.

Satu fakta hanya memiliki satu pemilik canonical:
- product decision → PRD;
- system behavior/business rule → SRS;
- technical interface → API Contract;
- perubahan implementasi internal → tidak perlu update canonical.

API Contract mereferensikan requirement ID SRS dan tidak mengulang business rule.
PRD tidak mengulang detail behavior atau interface teknis.

Jangan membaca PRD/SRS/API/plan penuh secara default. Gunakan targeted search
berdasarkan requirement ID, heading, route, endpoint, service, model, atau
istilah terkait.

Active plan hanya menjelaskan langkah implementasi dan mereferensikan canonical;
jangan menyalin ulang business rule panjang ke plan.

Jangan membuat spec/design document baru. Keputusan permanen masuk ke pemilik
canonical; histori perubahan cukup disimpan oleh Git.

Jangan membuat dokumen status kerja, log pengembangan, peta frontend, matriks
otorisasi, atau indeks requirement terpisah. Hindari dokumentasi ganda dan
jangan membuat dokumen pengganti untuk artefak yang dihapus.

## Implementation

- Pertahankan arsitektur repository; jangan menambah fitur di luar requirement.
- PHP >= 8.3 dan `declare(strict_types=1);`.
- Gunakan Thin Controller, Form Request, Service/Action, dan Query Scope sesuai pola repository.
- Pisahkan UI, akses data, business logic, dan integrasi.
- `AUTH-01`–`AUTH-07` ditegakkan di server/policy.
- Gunakan Bahasa Indonesia pada UI dan istilah `murid`.
- Reuse komponen UI existing; jangan redesign tanpa kebutuhan.
- Migration forward-only; jangan reset database shared/production.
- Jangan commit credential, `.env`, cache, raw payload, atau build.

## Verification

Gunakan bukti terkecil yang cukup: targeted test, targeted lint/syntax check, dan `git diff --check`.
Test authorization wajib bila perubahan menyentuh role, capability, policy, authorization, atau query scope.
Jangan menjalankan full test/build setelah setiap perubahan.

## Terminal

Agent boleh menjalankan command cepat, targeted, dan ber-output kecil: status/log/diff, `git grep`, targeted test/lint, dan diagnostik singkat.

Tanpa perintah eksplisit user, jangan jalankan:
- install/update dependency;
- dev server;
- `composer test` atau full test suite;
- `npm run build` atau full frontend checks;
- migration yang mengubah database;
- command interaktif, long-running, verbose, download, atau install.

Plan yang menyebut test/build/full verification bukan izin otomatis menjalankannya.

Jika helper non-esensial gagal karena permission/shell/path/environment, maksimal satu retry. Bila tetap gagal, hentikan dan gunakan fallback sederhana. Jangan menghabiskan token memperbaiki helper/ledger yang tidak memengaruhi fitur.
