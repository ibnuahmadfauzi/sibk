# Laporan Progress Revisi SIBK 3.2

Tanggal pembaruan: 19 September 2026  
Target integrasi: `cobasidebar`  
Branch Checkpoint 7B: `revisi-sibk-3-2-7b-runtime`

## Ringkasan status

Checkpoint 7B telah selesai diimplementasikan pada commit `4a5fcd2` dan
diintegrasikan ke `cobasidebar` melalui PR #26. Focused gate dan full gate
lulus. Status `MERGED` dan penghapusan branch sumber remote diverifikasi pada
penutupan checkpoint.

Checkpoint 7C belum dimulai. Tidak ada migration, drop tabel, atau drop kolom
pada Checkpoint 7B. Branch `main` tidak disentuh.

## Progress yang telah dilakukan

### 1. Cleanup runtime lama

- Menghapus model `FollowUp`, `CaseCoordination`, dan
  `ConsultationPrivateNote`.
- Menghapus controller, request, dan service untuk event tindak lanjut lama,
  koordinasi kasus, serta penyelesaian terpisah.
- Menghapus route plural `cases.follow-ups.*`, route
  `cases.coordinations.*`, route `cases.resolve*`, dan dua route preview
  terkait.
- Menghapus view formulir tindak lanjut berjadwal dan penyelesaian terpisah.
- Menghapus relasi model, ability policy, dan data view yang hanya dipakai
  runtime retired.

### 2. Consumer dan laporan

- Menghapus tiga method laporan legacy yang masih membaca tabel `follow_ups`.
- Menghapus branch export mati yang masih memakai `planned_date` dan
  `execution_date`.
- Mengalihkan test penyelesaian kasus ke `CaseService::update()` dengan aksi
  `complete`.
- Mempertahankan endpoint singular `cases.follow-up.update` karena endpoint
  ini mengubah klasifikasi tindak lanjut terkini pada record kasus dan bukan
  event `follow_ups`.

### 3. Akses Waka dan privasi

- Route mutasi lama kini menghasilkan 404.
- Akses detail Waka tetap hanya-baca, memakai proyeksi allowlist, dan tetap
  diaudit.
- `resources/views/pages/waka/case-detail.blade.php` dipertahankan karena
  sudah menjadi view aktif aman hasil Checkpoint 7A; menghapusnya akan
  mematahkan requirement akses baca Waka.

### 4. Test-first dan review

- Test Waka diubah lebih dahulu agar mengharapkan 404 pada endpoint retired;
  test gagal dengan 403 sebelum route dihapus dan lulus setelah cleanup.
- Focused test tambahan mencakup jalur Dapodik tertunda dan hardening route.
- Self-review menemukan consumer laporan mati yang luput dari daftar file plan;
  consumer tersebut dihapus sebelum full gate.
- Diff akhir menghapus 995 baris dan menambah 20 baris pada 25 file.

## Hasil verifikasi Checkpoint 7B

| Gate | Hasil |
|---|---|
| Focused gate | Lulus, 135 test dan 1.096 assertion |
| `composer test` | Lulus, 453 test dan 3.524 assertion |
| `php vendor/bin/pint --test` | Lulus |
| `npm run check:frontend` | Lulus |
| `npm run build` | Lulus |
| `composer validate --strict` | Lulus |
| `git diff --check` | Lulus |

Verifikasi memakai `APP_KEY` testing dan `CACHE_STORE=array` hanya pada
environment proses. Tidak ada `.env`, credential, dependency, atau artefak
build yang ditambahkan ke Git.

## Hasil scan consumer

Scan ketat tidak menemukan model/class retired, route plural, relasi
`followUps`/`coordinations`, private note, resolve terpisah, atau field event
lama pada `app`, `routes`, `resources`, dan `database/seeders`.

Scan plan yang lebih lebar masih menemukan nama `FollowUp` pada fitur aktif:
endpoint singular, request/controller/service dropdown, query laporan berbasis
`BkCase`, dashboard, dan JavaScript. Match ini sah karena berarti klasifikasi
tindak lanjut terkini, bukan consumer tabel `follow_ups`.

## Batas keras yang dipertahankan

- Tidak ada migration atau perubahan skema.
- Tabel/kolom/reference retired tetap tersedia sampai Checkpoint 7C.
- Tidak ada dependency baru.
- View detail aman Waka tidak dihapus.
- `main` tidak disentuh.
- Checkpoint 7C tidak boleh dimulai sebelum PR 7B terverifikasi `MERGED`.

## Risiko tersisa dan mitigasi

| Risiko | Mitigasi |
|---|---|
| Match scan `FollowUp` disalahartikan sebagai consumer event lama | Bedakan endpoint singular/klasifikasi aktif dari route plural/model event retired; scan ketat disimpan di handoff |
| Skema retired masih tersedia | Checkpoint 7C mengulang scan dependency sebelum migration forward-only |
| Drop skema menghilangkan data lama | Wajib backup operasional dan verifikasi upgrade pada database disposable; shared/production tidak di-reset |
| Detail Waka memuat narasi layanan | Policy hanya-baca, proyeksi allowlist, audit pembukaan, dan larangan ekspor massal tetap aktif |

## Progress selanjutnya

1. Pause pada batas Checkpoint 7B.
2. Saat pekerjaan dilanjutkan, sinkronkan `cobasidebar` dan mulai Checkpoint
   7C pada feature branch baru.
3. Ulangi scan dependency sebelum migration forward-only pada database
   disposable.

## Posisi aman untuk pause

Posisi aman adalah PR #26 berstatus `MERGED` ke `cobasidebar`, branch sumber
remote telah dihapus, dan `main` tidak disentuh. Checkpoint 7C belum dimulai.
