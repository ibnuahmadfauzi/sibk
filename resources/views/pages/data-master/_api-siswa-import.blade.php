<section class="sibk-panel mb-4" aria-labelledby="api-siswa-import-title">
    <div class="sibk-panel__header p-4 border-0 pb-0">
        <div>
            <h2 class="sibk-panel__title mb-1" id="api-siswa-import-title">Impor Data Murid dari API Siswa</h2>
            <p class="sibk-panel__subtitle text-muted small mb-0">
                Buat tahun ajaran di tab Tahun Ajaran &amp; Murid sebelum mengimpor daftar murid.
            </p>
        </div>
    </div>
    <div class="sibk-panel__body p-4">
        @if($preparationYears->where('is_active', false)->isEmpty())
            <div class="alert alert-info">
                Belum ada tahun ajaran persiapan. <a href="{{ route('data-master.index', ['tab' => 'tahun-ajaran']) }}">Buat tahun ajaran</a> terlebih dahulu.
            </div>
        @endif
        <h3 class="fs-6 fw-bold mb-3">Impor dari API Siswa</h3>
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
                >
                    Cek &amp; Pratinjau
                </button>
            </div>
        </form>

        <h4 class="fs-6 fw-bold mb-1">Impor CSV</h4>
        <p class="text-muted small">
            Gunakan sebagai cadangan. CSV UTF-8 maksimal 2 MiB dan 5.000 baris, dengan header
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
                <input
                    class="form-control"
                    type="file"
                    accept=".csv,text/csv"
                    id="roster_file"
                    name="file"
                    required
                >
            </div>
            <div class="col-12 col-md-auto">
                <button
                    type="submit"
                    class="btn btn-outline-primary w-100"
                    @disabled($preparationYears->where('is_active', false)->isEmpty())
                >
                    Impor CSV
                </button>
            </div>
        </form>
    </div>
</section>

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
