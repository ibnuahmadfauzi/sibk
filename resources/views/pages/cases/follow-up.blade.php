@extends('layouts.app-2')

@section('page-title', ($isEdit ? 'Ubah' : 'Tambah').' Tindak Lanjut - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <a href="{{ route('cases.show', $case) }}" class="text-decoration-none small">&larr; Kembali ke detail kasus</a>
                <h1>{{ $isEdit ? 'Ubah' : 'Tambah' }} Tindak Lanjut</h1>
                <p>{{ $case->registration_number }} &bull; {{ $case->identityName() }}</p>
            </div>
        </div>
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="sibk-panel">
            <form action="{{ $isEdit ? route('cases.follow-ups.update', [$case, $followUp]) : route('cases.follow-ups.store', $case) }}" method="POST" class="row g-4 p-4">
                @csrf
                @if($isEdit) @method('PATCH') @endif
                <div class="col-12 col-md-6">
                    <label class="form-label" for="follow_up_type_id">Jenis tindak lanjut <span class="text-danger">*</span></label>
                    <select class="form-select" id="follow_up_type_id" name="follow_up_type_id" required>
                        <option value="">Pilih jenis</option>
                        @foreach($followUpTypes as $type)<option value="{{ $type->id }}" @selected((string) old('follow_up_type_id', $followUp?->follow_up_type_id) === (string) $type->id)>{{ $type->label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status_id">Status pelaksanaan <span class="text-danger">*</span></label>
                    <select class="form-select" id="status_id" name="status_id" required>
                        <option value="">Pilih status</option>
                        @foreach($followUpStatuses as $status)<option value="{{ $status->id }}" @selected((string) old('status_id', $followUp?->status_id) === (string) $status->id)>{{ $status->label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6"><label class="form-label" for="planned_date">Tanggal rencana <span class="text-danger">*</span></label><input class="form-control" type="date" id="planned_date" name="planned_date" value="{{ old('planned_date', $followUp?->planned_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required></div>
                <div class="col-12 col-md-6"><label class="form-label" for="execution_date">Tanggal pelaksanaan</label><input class="form-control" type="date" id="execution_date" name="execution_date" value="{{ old('execution_date', $followUp?->execution_date?->format('Y-m-d')) }}"></div>
                <div class="col-12"><label class="form-label" for="result">Hasil</label><textarea class="form-control" id="result" name="result" rows="4">{{ old('result', $followUp?->result) }}</textarea><div class="form-text">Wajib diisi apabila status pelaksanaan Terlaksana.</div></div>
                <div class="col-12"><label class="form-label" for="next_plan">Rencana berikutnya</label><textarea class="form-control" id="next_plan" name="next_plan" rows="3">{{ old('next_plan', $followUp?->next_plan) }}</textarea></div>
                <div class="col-12 d-flex justify-content-end gap-2"><a href="{{ route('cases.show', $case) }}" class="btn btn-outline-secondary">Batal</a><button type="submit" class="btn btn-primary">Simpan Tindak Lanjut</button></div>
            </form>
        </div>
    </div>
@endsection
