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
            class="d-grid gap-3"
            data-api-siswa-import-form
            data-preview-url="{{ route('data-master.roster-imports.preview') }}"
        >
            @csrf
            <div>
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
            <div class="d-flex justify-content-end">
                <button
                    type="submit"
                    class="btn btn-primary"
                    data-api-siswa-preview-button
                    @disabled($preparationYears->where('is_active', false)->isEmpty())
                >
                    Cek &amp; Pratinjau
                </button>
            </div>
        </form>

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
