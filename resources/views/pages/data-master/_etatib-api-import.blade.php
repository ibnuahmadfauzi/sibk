<section class="sibk-panel sibk-data-master-tab-panel mb-4" aria-labelledby="etatib-api-title">

    <div class="sibk-panel__body p-4" data-saved-api-url data-reveal-url="{{ route('data-master.api-connections.reveal', ['provider' => 'etatib']) }}">
        @if($etatibAutomaticSetting['enabled'])
            <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-3" role="status">
                <div>
                    <strong class="d-block">Pembaruan otomatis aktif sesuai hari dan jam terpilih (WIB)</strong>
                    @if($etatibAutomaticSetting['last_run'])
                        <span class="d-block small mt-1">
                            Terakhir {{ $etatibAutomaticSetting['last_run']->started_at->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H.i') }}:
                            {{ match ($etatibAutomaticSetting['last_run']->status) {
                                \App\Models\ExternalSyncRun::STATUS_RUNNING => $etatibAutomaticSetting['last_run']->started_at->lte(now()->subSeconds(\App\Integrations\IntegrationOperationLock::LEASE_TTL_SECONDS))
                                    ? 'Perlu diperiksa' : 'Sedang berjalan',
                                \App\Models\ExternalSyncRun::STATUS_FAILED => 'Gagal',
                                \App\Models\ExternalSyncRun::STATUS_SUCCEEDED, \App\Models\ExternalSyncRun::STATUS_WARNING => 'Selesai',
                                default => 'Perlu diperiksa',
                            } }}
                        </span>
                    @endif
                </div>
                <form
                    action="{{ route('data-master.etatib.automatic.sync') }}"
                    method="POST"
                >
                    @csrf
                    <button class="btn btn-success btn-sm" type="submit" data-action-tooltip="Perbarui catatan pelanggaran dari tautan e-Tatib yang tersimpan.">Sinkronkan Sekarang</button>
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
            data-saved-api-source-form
        >
            @csrf
            @include('pages.data-master._saved-api-url', ['provider' => 'etatib', 'label' => 'API e-Tatib', 'inputId' => 'etatib_api_url', 'placeholder' => 'https://api-sekolah.example/pelanggaran'])
        </form>
        @include('pages.data-master._saved-api-url-modal', ['provider' => 'etatib', 'label' => 'API e-Tatib'])
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

@if($savedApiUrls['etatib'] ?? false)
    @php
        $scheduleErrors = $errors->getBag('etatib_schedule');
        $scheduleDays = old('schedule_days', $etatibAutomaticSetting['schedule_days']);
        $scheduleDays = is_array($scheduleDays) ? array_map('strval', $scheduleDays) : [];
        $scheduleTimes = old('schedule_times', $etatibAutomaticSetting['schedule_times']);
        $scheduleTimes = is_array($scheduleTimes) ? array_filter($scheduleTimes, 'is_string') : [];
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    @endphp
    <section class="sibk-panel mb-4" aria-labelledby="etatib-schedule-title">
        <div class="sibk-panel__body p-4">
            <h2 class="fs-6 fw-bold" id="etatib-schedule-title">Jadwal Pembaruan e-Tatib</h2>
            <p class="small text-muted">Pilih hari dan jam dalam WIB. Mengubah jadwal tidak mengaktifkan atau menonaktifkan pembaruan otomatis.</p>
            @if($scheduleErrors->has('etatib_schedule'))<div class="alert alert-warning" role="alert">{{ $scheduleErrors->first('etatib_schedule') }}</div>@endif
            <form action="{{ route('data-master.etatib.automatic.schedule') }}" method="POST" class="d-grid gap-3" data-etatib-schedule-form>
                @csrf
                @method('PATCH')
                <fieldset>
                    <legend class="form-label fs-6">Hari</legend>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach($dayNames as $day => $name)
                            <label class="form-check-label d-flex align-items-center gap-2">
                                <input class="form-check-input m-0" type="checkbox" name="schedule_days[]" value="{{ $day }}" @checked(in_array((string) $day, $scheduleDays, true))>
                                {{ $name }}
                            </label>
                        @endforeach
                    </div>
                    @if($scheduleErrors->has('schedule_days'))<div class="text-danger small mt-1" role="alert">{{ $scheduleErrors->first('schedule_days') }}</div>@endif
                </fieldset>
                <div>
                    <div class="form-label">Jam (WIB)</div>
                    <div class="d-grid gap-2" data-etatib-schedule-times>
                        @foreach($scheduleTimes as $time)
                            <div class="d-flex gap-2" data-etatib-schedule-time-row>
                                <input class="form-control" type="time" name="schedule_times[]" value="{{ $time }}" aria-label="Jam pembaruan {{ $loop->iteration }}" required>
                                <button class="btn btn-outline-secondary" type="button" data-etatib-schedule-remove aria-label="Hapus jam pembaruan {{ $loop->iteration }}">Hapus</button>
                            </div>
                        @endforeach
                    </div>
                    <button class="btn btn-outline-primary btn-sm mt-2" type="button" data-etatib-schedule-add>Tambah jam</button>
                    @if($scheduleErrors->has('schedule_times'))<div class="text-danger small mt-1" role="alert">{{ $scheduleErrors->first('schedule_times') }}</div>@endif
                </div>
                <div>
                    <label class="form-label" for="etatib_schedule_password">Kata sandi saat ini</label>
                    <input class="form-control" id="etatib_schedule_password" type="password" name="current_password" autocomplete="current-password" required data-etatib-schedule-password
                        @if($scheduleErrors->has('current_password')) aria-invalid="true" aria-describedby="etatib_schedule_password_error" @endif>
                    @if($scheduleErrors->has('current_password'))<div class="invalid-feedback d-block" id="etatib_schedule_password_error">{{ $scheduleErrors->first('current_password') }}</div>@endif
                </div>
                <div><button class="btn btn-primary" type="submit">Simpan jadwal</button></div>
            </form>
        </div>
    </section>
@endif

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
                            <td>{{ $decision->approved_at->copy()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H.i') }}</td>
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
                Penonaktifan menghentikan jadwal otomatis. Tautan API tersimpan tetap tersedia untuk pratinjau dan sinkronisasi manual.
            </p>
            <form
                class="row g-2 align-items-end"
                action="{{ route('data-master.etatib.automatic.destroy') }}"
                method="POST"
                data-confirm-submit
                data-confirm-message="Nonaktifkan jadwal otomatis e-Tatib? Tautan API tersimpan tetap tersedia."
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
