<div class="row g-3">
    <div class="col-12 col-md-6">
        <strong class="d-block mb-1">Latar Belakang Masalah</strong>
        <p class="mb-0 text-break sibk-case-detail__text">{{ $consultation->problem ?: '—' }}</p>
    </div>
    <div class="col-12 col-md-6">
        <strong class="d-block mb-1">Penanganan</strong>
        <p class="mb-0 text-break sibk-case-detail__text">{{ $consultation->handling ?: '—' }}</p>
    </div>
</div>
