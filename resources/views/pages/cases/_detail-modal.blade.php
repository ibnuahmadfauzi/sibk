@php
    $membership = $case->student?->classMemberships
        ->sortByDesc('effective_from')
        ->first(fn ($item) => $item->effective_from->lte($case->service_date)
            && ($item->effective_until === null || $item->effective_until->gte($case->service_date)));
    $owner = $case->activeOwnerAssignment($case->service_date)?->teacher;
@endphp
<div class="modal-header">
    <h2
        class="modal-title fs-5"
        id="case-modal-title"
    >
        Detail Permasalahan
    </h2>
    <button
        class="btn-close"
        type="button"
        data-bs-dismiss="modal"
        aria-label="Tutup"
    ></button>
</div>
<div class="modal-body">
    <dl class="row mb-0">
        <dt class="col-sm-4">Nama</dt>
        <dd class="col-sm-8">{{ $case->identityName() }}</dd>

        <dt class="col-sm-4">Tanggal</dt>
        <dd class="col-sm-8">
            {{ $case->service_date->locale('id')->translatedFormat('d F Y') }}
        </dd>

        <dt class="col-sm-4">Kelas</dt>
        <dd class="col-sm-8">{{ $membership?->classroom?->name ?? '—' }}</dd>

        <dt class="col-sm-4">Jenis Masalah</dt>
        <dd class="col-sm-8">{{ $case->serviceField?->label ?? '—' }}</dd>

        <dt class="col-sm-4">Status</dt>
        <dd class="col-sm-8">{{ $case->status?->label }}</dd>

        <dt class="col-sm-4">Guru BK</dt>
        <dd class="col-sm-8">{{ $owner?->name ?? '—' }}</dd>

        <dt class="col-sm-4">Latar Belakang Masalah</dt>
        <dd class="col-sm-8 text-break">{{ $case->initial_info }}</dd>

        <dt class="col-sm-4">Penanganan</dt>
        <dd class="col-sm-8 text-break">{{ $case->initial_action }}</dd>

        <dt class="col-sm-4">Catatan Penyelesaian</dt>
        <dd class="col-sm-8 text-break">{{ $case->resolution_summary ?: '—' }}</dd>
    </dl>
</div>
<div class="modal-footer">
    <button
        class="btn btn-outline-secondary"
        type="button"
        data-bs-dismiss="modal"
    >
        Tutup
    </button>
</div>
