<div aria-labelledby="api-siswa-import-title">
    <div class="sibk-panel__header p-4 border-0 pb-0">
        <div>
            <h2 class="sibk-panel__title mb-1" id="api-siswa-import-title">Impor Data Murid dari API Siswa</h2>
            <p class="sibk-panel__subtitle text-muted small mb-0">
                Masukkan link, periksa pratinjau, lalu pilih Impor Data jika sudah sesuai.
            </p>
        </div>
    </div>
    <div class="sibk-panel__body p-4">
        @if($preparationYears->where('is_active', false)->isEmpty())
            <div class="alert alert-info">
                Belum ada tahun ajaran persiapan. <a href="#academic-year-preparation-title">Buat tahun ajaran</a> terlebih dahulu.
            </div>
        @endif
        @error('academic_year')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        @error('api_url')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        @error('data')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
        @error('file')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

        <form
            action="{{ route('data-master.roster-imports.store') }}"
            method="POST"
            class="row g-2 align-items-end mb-4"
            data-api-siswa-import-form
            data-preview-url="{{ route('data-master.roster-imports.preview') }}"
        >
            @csrf
            <div class="col-12 col-md">
                <label class="form-label small" for="api_siswa_url">Link API Siswa</label>
                <input
                    class="form-control"
                    type="url"
                    id="api_siswa_url"
                    name="api_url"
                    maxlength="2048"
                    placeholder="https://api-sekolah.example/data-siswa"
                    autocomplete="off"
                    required
                >
                <div class="form-text">
                    Link dipakai satu kali untuk mengambil data dan tidak disimpan oleh SIBK.
                </div>
            </div>
            <div class="col-12 col-md-auto">
                <button
                    type="submit"
                    class="btn btn-primary w-100"
                    data-api-siswa-preview-button
                    @disabled($preparationYears->where('is_active', false)->isEmpty())
                >
                    Cek &amp; Pratinjau
                </button>
            </div>
        </form>

        <details class="border-top pt-3" @if($errors->has('file')) open @endif>
            <summary class="fw-semibold text-primary py-3">Impor CSV (cadangan)</summary>
            <p class="text-muted small mt-3">
                CSV UTF-8 maksimal 2 MiB dan 5.000 baris, dengan header
                <code>nisn,nama,rombel,tahun_pelajaran</code>.
                Semua tahun pelajaran harus sudah dibuat dan belum aktif.
            </p>
            <form
                action="{{ route('data-master.roster-imports.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="row g-2 align-items-end mb-3"
            >
                @csrf
                <div class="col-12 col-md">
                    <label class="form-label small" for="roster_file">Pilih berkas CSV</label>
                    <input class="form-control" type="file" accept=".csv,text/csv" id="roster_file" name="file" required>
                </div>
                <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-outline-primary w-100" @disabled($preparationYears->where('is_active', false)->isEmpty())>Impor CSV</button>
                </div>
            </form>
        </details>

        @if($studentCount > 0)
        <div class="border-top pt-3 mt-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h3 class="fs-6 fw-bold mb-1">Periksa hasil impor</h3>
                <p class="mb-0 small text-muted">Pastikan murid dan rombel yang masuk sudah sesuai.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('data-master.students.index') }}">Periksa Data Murid</a>
        </div>
        @endif
    </div>
</div>

<div
    class="modal fade"
    id="api-siswa-preview-modal"
    tabindex="-1"
    aria-labelledby="api-siswa-preview-title"
    aria-hidden="true"
    data-api-siswa-preview-modal
>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="api-siswa-preview-title">Pratinjau API Siswa</h2>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                ></button>
            </div>
            <div class="modal-body" data-api-siswa-preview-body>
                <p class="text-muted mb-0">Masukkan link API Siswa untuk melihat pratinjau.</p>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal"
                >Batal</button>
                <button
                    type="button"
                    class="btn btn-primary"
                    data-api-siswa-confirm-import
                    disabled
                >Impor Data</button>
            </div>
        </div>
    </div>
</div>
