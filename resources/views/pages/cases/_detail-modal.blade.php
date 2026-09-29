@php
    $badgeTone = match($case->status?->code) {
        'selesai' => 'success',
        'sedang_diproses' => 'info',
        'membutuhkan_tindak_lanjut' => 'warning',
        default => 'primary',
    };
@endphp
<div class="modal-header border-0 pb-2">
    <h2 class="modal-title fs-5 fw-bold" id="case-modal-title">Detail Kasus BK</h2>
    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body pt-2 sibk-case-detail">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <section class="sibk-panel h-100" aria-labelledby="case-student-title">
                <div class="sibk-case-detail__heading">
                    <span class="sibk-case-detail__icon text-primary bg-primary-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path stroke-linecap="round" d="M4 21v-2a8 8 0 0116 0v2"/></svg></span>
                    <h3 class="fs-6 fw-bold mb-0" id="case-student-title">Data Murid</h3>
                </div>
                <dl class="sibk-case-detail__fields mb-0">
                    <div><dt>NISN</dt><dd>{{ $case->identityNisn() ?: '—' }}</dd></div>
                    <div><dt>Nama</dt><dd>{{ $case->identityName() }}</dd></div>
                    <div><dt>Rombel</dt><dd>{{ $case->classroom?->name ?? '—' }}</dd></div>
                </dl>
            </section>
        </div>
        <div class="col-12 col-md-6">
            <section class="sibk-panel h-100" aria-labelledby="case-service-title">
                <div class="sibk-case-detail__heading">
                    <span class="sibk-case-detail__icon text-primary bg-primary-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linejoin="round" d="M6 3h8l4 4v14H6zM14 3v5h4"/><path stroke-linecap="round" d="M9 12h6m-6 4h6"/></svg></span>
                    <h3 class="fs-6 fw-bold mb-0" id="case-service-title">Informasi Layanan</h3>
                </div>
                <dl class="sibk-case-detail__fields mb-0">
                    <div><dt>Sumber</dt><dd>{{ $case->source?->label ?? '—' }}</dd></div>
                    <div><dt>Tanggal Layanan</dt><dd>{{ $case->service_date->locale('id')->translatedFormat('d F Y') }}<span class="d-block text-muted fw-normal">({{ $case->service_date->locale('id')->translatedFormat('l') }})</span></dd></div>
                    <div><dt>Jenis Masalah</dt><dd>{{ $case->serviceField?->label ?? '—' }}</dd></div>
                    <div><dt>Status</dt><dd><span class="sibk-badge sibk-badge--{{ $badgeTone }}">{{ $case->status?->label ?? '—' }}</span></dd></div>
                </dl>
            </section>
        </div>
        <div class="col-12">
            <section class="sibk-panel" aria-labelledby="case-notes-title">
                <div class="sibk-case-detail__heading">
                    <span class="sibk-case-detail__icon text-primary bg-primary-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linejoin="round" d="M6 3h8l4 4v14H6zM14 3v5h4"/><path stroke-linecap="round" d="M9 12h6m-6 4h6"/></svg></span>
                    <h3 class="fs-6 fw-bold mb-0" id="case-notes-title">Catatan Permasalahan</h3>
                </div>
                <div class="row g-0 p-3">
                    @foreach(['Latar Belakang' => $case->initial_info, 'Penanganan' => $case->initial_action, 'Ringkasan' => $case->resolution_summary] as $heading => $text)
                        <div class="col-12 col-md-4 sibk-case-detail__note">
                            <h4 class="small fw-bold mb-1">{{ $heading }}</h4>
                            <p class="small text-muted text-break mb-0 sibk-case-detail__text">{{ $text ?: '—' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
        <div class="col-12">
            <section class="sibk-panel" aria-labelledby="case-history-title">
                <div class="sibk-case-detail__heading">
                    <span class="sibk-case-detail__icon text-success bg-success-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg></span>
                    <h3 class="fs-6 fw-bold mb-0" id="case-history-title">Riwayat Tindak Lanjut</h3>
                </div>
                <ol class="sibk-case-detail__history list-unstyled p-3 mb-0">
                    @forelse($case->followUps as $followUp)
                        <li class="sibk-case-detail__history-entry">
                            <div class="small text-center">
                                <time class="fw-semibold" datetime="{{ $followUp->follow_up_date->toDateString() }}">{{ $followUp->follow_up_date->locale('id')->translatedFormat('d F Y') }}</time>
                                <span class="d-block text-muted">({{ $followUp->follow_up_date->locale('id')->translatedFormat('l') }})</span>
                            </div>
                            <div class="sibk-case-detail__history-card border rounded p-3">
                                <h4 class="small fw-bold mb-0">{{ $followUp->followUpType?->label ?? '—' }}</h4>
                                @if($followUp->notes)<p class="small text-muted text-break mt-1 mb-0 sibk-case-detail__text">{{ $followUp->notes }}</p>@endif
                            </div>
                        </li>
                    @empty
                        <li class="small text-muted">Belum ada riwayat tindak lanjut.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
</div>
