@php
    $sessionDate = $consultation->session_date->locale('id');
@endphp
<div class="row g-3">
    <div class="col-12">
        <section class="sibk-panel" aria-labelledby="consultation-general-title">
            <div class="sibk-case-detail__heading">
                <span class="sibk-case-detail__icon text-primary bg-primary-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path stroke-linecap="round" d="M4 21v-2a8 8 0 0116 0v2"/></svg></span>
                <h3 class="fs-6 fw-bold mb-0" id="consultation-general-title">Informasi Umum</h3>
            </div>
            <dl class="sibk-case-detail__fields mb-0">
                <div><dt>Nama Murid</dt><dd>{{ $consultation->identityName() }}</dd></div>
                <div><dt>Tanggal Layanan</dt><dd>{{ $sessionDate->translatedFormat('d F Y') }}<span class="d-block text-muted fw-normal">({{ $sessionDate->translatedFormat('l') }})</span></dd></div>
                <div><dt>NISN</dt><dd>{{ $consultation->identityNisn() ?: '—' }}</dd></div>
                <div><dt>Jenis Layanan</dt><dd>{{ $consultation->serviceField?->label ?? '—' }}</dd></div>
                <div><dt>Rombel/Kelas</dt><dd>{{ $consultation->classroom?->name ?? '—' }}</dd></div>
            </dl>
        </section>
    </div>
    <div class="col-12">
        <section class="sibk-panel" aria-labelledby="consultation-notes-title">
            <div class="sibk-case-detail__heading">
                <span class="sibk-case-detail__icon text-primary bg-primary-subtle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linejoin="round" d="M6 3h8l4 4v14H6zM14 3v5h4"/><path stroke-linecap="round" d="M9 12h6m-6 4h6"/></svg></span>
                <h3 class="fs-6 fw-bold mb-0" id="consultation-notes-title">Catatan Konsultasi</h3>
            </div>
            <div class="row g-0 p-3">
                @foreach ([
                    'Latar Belakang Masalah' => $consultation->problem,
                    'Penanganan' => $consultation->handling,
                    'Hasil / Ringkasan' => $consultation->result,
                ] as $heading => $text)
                    <div class="col-12 col-md-4 sibk-case-detail__note">
                        <h4 class="small fw-bold mb-1">{{ $heading }}</h4>
                        <p class="small text-muted text-break mb-0 sibk-case-detail__text">{{ $text ?: '—' }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</div>
