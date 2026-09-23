@php
    $detailStudent = $consultation->student
        ?? $consultation->temporaryStudent?->reconciledStudent;
    $detailClass = $detailStudent?->classMemberships
        ->first(fn ($membership) => $membership->effective_from->lte($consultation->session_date)
            && ($membership->effective_until === null
                || $membership->effective_until->gte($consultation->session_date)))
        ?->classroom?->name ?? '—';
@endphp
<div data-consultation-detail-modal>
    <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div class="sibk-page-header__copy">
            <a
                class="text-decoration-none small"
                href="{{ route('cases.index', ['tab' => 'konsultasi']) }}"
            >
                &larr; Kembali ke daftar
            </a>
            <h1 id="case-modal-title">Detail Konsultasi</h1>
            <p>
                {{ $consultation->identityName() }}
                &mdash;
                {{ $consultation->session_date->locale('id')->translatedFormat('d F Y') }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($modal)
                <button
                    class="btn btn-outline-secondary"
                    type="button"
                    data-bs-dismiss="modal"
                >
                    Tutup
                </button>
            @endif
            @if($canUpdateConsultation)
                <a
                    class="btn btn-primary"
                    href="{{ route('consultations.edit', $consultation) }}"
                    data-modal-url="{{ route('consultations.edit', [$consultation, 'modal' => 1]) }}"
                    data-confirm-message="Layanan ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?"
                    @if($modal)
                        onclick="if (!window.confirm(this.dataset.confirmMessage)) { event.stopPropagation(); return false; }"
                    @endif
                >
                    Edit
                </a>
            @endif
            @if($canArchiveConsultation)
                <form
                    action="{{ route('consultations.destroy', $consultation) }}"
                    method="POST"
                    data-confirm-submit
                    data-confirm-message="Arsipkan konsultasi ini?"
                    @if($modal)
                        onsubmit="if (!window.confirm(this.dataset.confirmMessage)) { event.stopPropagation(); return false; }"
                    @endif
                >
                    @csrf
                    @method('DELETE')
                    <button
                        class="btn btn-outline-danger"
                        type="submit"
                    >
                        Arsipkan
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="sibk-panel h-100">
                <div class="sibk-panel__header p-4 border-bottom">
                    <h2 class="fs-5 m-0">Informasi Layanan</h2>
                </div>
                <div class="sibk-panel__body p-4">
                    <p>
                        <span class="text-muted small d-block">Murid</span>
                        <strong>{{ $consultation->identityName() }}</strong>
                    </p>
                    <p>
                        <span class="text-muted small d-block">Kelas saat layanan</span>
                        {{ $detailClass }}
                    </p>
                    <p>
                        <span class="text-muted small d-block">Tanggal</span>
                        {{ $consultation->session_date->locale('id')->translatedFormat('d F Y') }}
                    </p>
                    <p>
                        <span class="text-muted small d-block">Jenis Masalah</span>
                        {{ $consultation->serviceField->label }}
                    </p>
                    <p class="mb-0">
                        <span class="text-muted small d-block">Guru BK</span>
                        {{ $consultation->counselor->name }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="sibk-panel">
                <div class="sibk-panel__body p-4">
                    <h2 class="fs-6 fw-bold">Latar Belakang Masalah</h2>
                    <p>{{ $consultation->problem }}</p>

                    <h2 class="fs-6 fw-bold">Penanganan</h2>
                    <p>{{ $consultation->handling }}</p>

                    <h2 class="fs-6 fw-bold">Hasil</h2>
                    <p class="mb-0">{{ $consultation->result ?: '—' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
