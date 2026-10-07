<section class="sibk-panel sibk-data-master-tab-panel mb-4" aria-labelledby="etatib-api-title">

    <div class="sibk-panel__body p-4">
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
            class="d-grid gap-2"
            data-etatib-api-form
            data-preview-url="{{ route('data-master.etatib.preview') }}"
            data-dapodik-url="{{ route('data-master.index', ['tab' => 'dapodik']) }}"
            data-manual-sync-url="{{ route('data-master.etatib.sync') }}"
            data-automatic-sync-url="{{ route('data-master.etatib.automatic.store') }}"
            data-duplicate-revoke-url="{{ route('data-master.etatib.duplicates.destroy', ['decision' => '__DECISION__']) }}"
        >
            @csrf
            <label class="form-label small mb-0" for="etatib_api_url">Tautan API e-Tatib</label>
            <div class="row g-2 align-items-stretch">
                <div class="col-12 col-md">
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
                </div>
                <div class="col-12 col-md-auto">
                    <button
                        type="submit"
                        class="btn btn-primary w-100 h-100"
                        data-etatib-preview-button
                    >
                        Tinjau Data
                    </button>
                </div>
            </div>
            <!-- <div class="form-text mt-0">
                Tautan tidak ditampilkan lagi; jadwal otomatis menyimpannya terenkripsi.
            </div> -->
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
                <h2 class="modal-title fs-5" id="etatib-api-preview-title">Pratinjau e-Tatib</h2>
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
                <div class="w-100" data-etatib-automatic-fields hidden>
                    <label class="form-label small" for="etatib_automatic_current_password">
                        Kata sandi saat ini
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
                    Sinkronkan Data
                </button>
                <button type="button" class="btn btn-primary" data-etatib-confirm-automatic disabled>
                    Aktifkan Otomatis
                </button>
            </div>
        </div>
    </div>
</div>

@if($etatibDuplicateDecisions?->isNotEmpty())
    <section class="sibk-panel mb-4" aria-labelledby="etatib-duplicate-decisions-title">
        <div class="sibk-panel__header">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h2 class="sibk-panel__title fs-6 fw-semibold mb-0" id="etatib-duplicate-decisions-title">Keputusan Duplikasi</h2>
                    <span class="badge text-bg-secondary">{{ $etatibDuplicateDecisions->total() }} aktif</span>
                </div>
                <p class="sibk-panel__subtitle text-muted">Keputusan aktif dapat dibatalkan tanpa menghapus riwayat.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table sibk-table mb-0">
                <thead><tr><x-sort-header name="murid" label="Murid" sort-param="duplicate_sort" direction-param="duplicate_direction" page-param="duplicate_page" /><th scope="col">Keputusan</th><x-sort-header name="waktu" label="Disetujui" sort-param="duplicate_sort" direction-param="duplicate_direction" page-param="duplicate_page" /><th scope="col">Aksi</th></tr></thead>
                <tbody>
                    @foreach($etatibDuplicateDecisions as $decision)
                        <tr>
                            <td>{{ \App\Support\StudentName::display($decision->source_name) }}<span class="d-block small text-muted">NISN {{ $decision->source_nisn }}</span></td>
                            <td>{{ $decision->copy_count }} salinan dianggap satu kejadian</td>
                            <td>{{ $decision->approved_at->locale('id')->translatedFormat('d M Y, H.i') }}</td>
                            <td>
                                <form action="{{ route('data-master.etatib.duplicates.destroy', $decision) }}" method="POST"
                                    data-app-confirm-submit data-confirm-title="Batalkan keputusan duplikasi?"
                                    data-confirm-message="Sinkronisasi berikutnya memerlukan tinjauan ulang untuk kelompok ini. Riwayat tersimpan tidak dihapus."
                                    data-confirm-subject="{{ \App\Support\StudentName::display($decision->source_name) }}" data-confirm-action="Batalkan Keputusan" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm p-0 sibk-icon-button sibk-report-control text-danger" type="submit" title="Batalkan Keputusan" aria-label="Batalkan Keputusan"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 7h11a6 6 0 0 1 0 12M7 3 3 7l4 4"/></svg></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($etatibDuplicateDecisions->hasPages())
            <div class="p-3">{{ $etatibDuplicateDecisions->links('pagination.data-master') }}</div>
        @endif
    </section>
@endif

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
