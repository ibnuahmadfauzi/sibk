@extends('layouts.app-2')

@section('page-title', 'Ubah Kasus - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-103-EDIT">
        <div class="sibk-page-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('cases.show', $case) }}" class="btn btn-icon btn-light" aria-label="Kembali">&larr;</a>
                <div class="sibk-page-header__copy m-0">
                    <h1 class="mb-1">Ubah Kasus</h1>
                    <p class="mb-0">Perbarui data pelayanan tanpa mengubah identitas murid.</p>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('cases.update', $case) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Murid dan Konteks Layanan</h2></div>
                <div class="sibk-panel__body p-4 row g-4">
                    <div class="col-12">
                        <label class="form-label">Murid</label>
                        <div class="form-control bg-light">{{ $case->identityName() }} &mdash; NISN {{ $case->identityNisn() }}</div>
                        <div class="form-text">Identitas murid dan tautan sumber integrasi tidak dapat diubah dari halaman ini.</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="case_source_id">Sumber Kasus</label>
                        <select class="form-select" id="case_source_id" name="case_source_id" required>
                            @foreach($caseSources as $source)<option value="{{ $source->id }}" @selected((string) old('case_source_id', $case->case_source_id) === (string) $source->id)>{{ $source->label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="service_field_id">Bidang Layanan</label>
                        <select class="form-select" id="service_field_id" name="service_field_id" required>
                            @foreach($serviceFields as $field)<option value="{{ $field->id }}" @selected((string) old('service_field_id', $case->service_field_id) === (string) $field->id)>{{ $field->label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="status_id">Status</label>
                        <select class="form-select" id="status_id" name="status_id" required>
                            @foreach($caseStatuses as $status)<option value="{{ $status->id }}" @selected((string) old('status_id', $case->status_id) === (string) $status->id)>{{ $status->label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4"><label class="form-label" for="service_date">Tanggal Layanan</label><input type="date" class="form-control" id="service_date" name="service_date" value="{{ old('service_date', $case->service_date->format('Y-m-d')) }}" required></div>
                    <div class="col-12 col-md-8"><label class="form-label" for="referrer">Pihak Perujuk</label><input class="form-control" id="referrer" name="referrer" value="{{ old('referrer', $case->referrer) }}" maxlength="150"></div>
                </div>
            </div>

            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Informasi Penanganan</h2></div>
                <div class="sibk-panel__body p-4 row g-4">
                    <div class="col-12 col-md-6"><label class="form-label" for="initial_info">Informasi Awal</label><textarea class="form-control" id="initial_info" name="initial_info" rows="5" required>{{ old('initial_info', $case->initial_info) }}</textarea></div>
                    <div class="col-12 col-md-6"><label class="form-label" for="initial_action">Penanganan Awal</label><textarea class="form-control" id="initial_action" name="initial_action" rows="5" required>{{ old('initial_action', $case->initial_action) }}</textarea></div>
                    <div class="col-12"><label class="form-label" for="internal_note">Catatan Internal</label><textarea class="form-control" id="internal_note" name="internal_note" rows="3">{{ old('internal_note', $case->internal_note) }}</textarea></div>
                </div>
            </div>

            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Ringkasan Penanganan untuk Waka</h2><p class="sibk-panel__subtitle">Tuliskan tindakan operasional dan perkembangan umum tanpa percakapan konseling atau catatan pribadi.</p></div>
                <div class="sibk-panel__body p-4">
                    <textarea class="form-control" id="waka_summary" name="waka_summary" rows="4" maxlength="500">{{ old('waka_summary', $case->waka_summary) }}</textarea>
                    <div class="form-text">Opsional saat status Baru dicatat; wajib ketika kasus mulai diproses. Maksimal 500 karakter.</div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-5">
                <a href="{{ route('cases.show', $case) }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection
