<section class="sibk-panel sibk-data-master-tab-panel mb-4" aria-labelledby="etatib-api-title">
    <div class="sibk-panel__header p-4 border-0 pb-0">
        <div>
            <h2 class="sibk-panel__title mb-1" id="etatib-api-title">Sinkronkan Data e-Tatib</h2>
            <p class="sibk-panel__subtitle text-muted small mb-0">
                Masukkan tautan API, tinjau pelanggaran, lalu sinkronkan.
            </p>
        </div>
    </div>
    <div class="sibk-panel__body p-4">
        @if($errors->getBag('etatib_api')->any())
            <div class="alert alert-danger" role="alert">
                {{ $errors->getBag('etatib_api')->first() }}
            </div>
        @endif
        @if($errors->getBag('etatib_automatic')->any())
            <div class="alert alert-danger" role="alert">
                {{ $errors->getBag('etatib_automatic')->first() }}
            </div>
        @endif

        @if($etatibAutomaticSetting['enabled'])
            <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-3" role="status">
                <div>
                    <strong class="d-block">Aktif: Senin-Jumat, 15.00 WIB</strong>
                    @if($etatibAutomaticSetting['last_run'])
                        <span class="d-block small mt-1">
                            Terakhir {{ $etatibAutomaticSetting['last_run']->started_at->locale('id')->translatedFormat('d M Y, H.i') }}:
                            {{ $etatibAutomaticSetting['last_run']->status === 'failed' ? 'Gagal' : 'Selesai' }}
                        </span>
                    @endif
                </div>
                <form
                    action="{{ route('data-master.etatib.automatic.sync') }}"
                    method="POST"
                >
                    @csrf
                    <button class="btn btn-success btn-sm" type="submit">Sinkronkan Sekarang</button>
                </form>
            </div>
        @endif

        <form
            id="etatib-api-form"
            action="{{ route('data-master.etatib.sync') }}"
            method="POST"
            class="row g-2 align-items-end"
            data-etatib-api-form
            data-preview-url="{{ route('data-master.etatib.preview') }}"
            data-manual-sync-url="{{ route('data-master.etatib.sync') }}"
            data-automatic-sync-url="{{ route('data-master.etatib.automatic.store') }}"
        >
            @csrf
            <div class="col-12 col-md">
                <label class="form-label small" for="etatib_api_url">Tautan API e-Tatib</label>
                <input
                    class="form-control"
                    type="url"
                    id="etatib_api_url"
                    name="api_url"
                    maxlength="2048"
                    placeholder="https://api-sekolah.example/pelanggaran"
                    autocomplete="off"
                    required
                >
                <div class="form-text">
                    Tautan tidak ditampilkan lagi; jadwal otomatis menyimpannya terenkripsi.
                </div>
            </div>
            <div class="col-12 col-md-auto">
                <button
                    type="submit"
                    class="btn btn-primary w-100"
                    data-etatib-preview-button
                >
                    Tinjau Data
                </button>
            </div>
        </form>
    </div>
</section>

<div
    class="modal fade"
    id="etatib-api-preview-modal"
    tabindex="-1"
    aria-labelledby="etatib-api-preview-title"
    aria-hidden="true"
    data-etatib-preview-modal
>
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="etatib-api-preview-title">Pratinjau API e-Tatib</h2>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                ></button>
            </div>
            <div class="modal-body" data-etatib-preview-body>
                <p class="text-muted mb-0">Masukkan link API e-Tatib untuk melihat pratinjau.</p>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <label class="form-label small" for="etatib_automatic_current_password">
                        Kata sandi saat ini untuk menyimpan jadwal otomatis
                    </label>
                    <input
                        class="form-control"
                        id="etatib_automatic_current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        form="etatib-api-form"
                        disabled
                        data-etatib-automatic-password
                    >
                </div>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-outline-primary" data-etatib-confirm disabled>
                    Sinkronkan Data Sekali
                </button>
                <button type="button" class="btn btn-primary" data-etatib-confirm-automatic disabled>
                    Simpan &amp; Aktifkan Otomatis
                </button>
            </div>
        </div>
    </div>
</div>

@if($etatibAutomaticSetting['enabled'])
    <section class="sibk-panel mb-4" aria-labelledby="etatib-automatic-disable-title">
        <div class="sibk-panel__body p-4">
            <h2 class="fs-6 fw-bold" id="etatib-automatic-disable-title">Nonaktifkan Pembaruan Otomatis</h2>
            <p class="text-muted small">
                Penonaktifan menghapus link API yang tersimpan. Sinkronisasi sekali pakai tetap tersedia.
            </p>
            <form
                class="row g-2 align-items-end"
                action="{{ route('data-master.etatib.automatic.destroy') }}"
                method="POST"
                data-confirm-submit
                data-confirm-message="Nonaktifkan jadwal otomatis dan hapus link API e-Tatib tersimpan?"
            >
                @csrf
                @method('DELETE')
                <div class="col-12 col-md">
                    <label class="form-label small" for="etatib_disable_current_password">Kata sandi saat ini</label>
                    <input
                        class="form-control"
                        id="etatib_disable_current_password"
                        name="current_password"
                        type="password"
                        autocomplete="current-password"
                        required
                    >
                </div>
                <div class="col-12 col-md-auto">
                    <button class="btn btn-outline-danger w-100" type="submit">Nonaktifkan</button>
                </div>
            </form>
        </div>
    </section>
@endif
