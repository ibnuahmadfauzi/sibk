@extends('layouts.app-2')

@section('page-title', 'Selesaikan Kasus - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-106">
        <div class="sibk-page-header mb-4"><div class="sibk-page-header__copy"><a href="{{ route('cases.show', $case) }}" class="text-decoration-none small">&larr; Kembali ke detail kasus</a><h1>Selesaikan Kasus</h1><p>{{ $case->registration_number }} &bull; {{ $case->identityName() }}</p></div></div>
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <div class="sibk-panel">
            <form action="{{ route('cases.resolve', $case) }}" method="POST" class="row g-4 p-4">
                @csrf
                <div class="col-12 col-md-5"><label class="form-label" for="closed_at">Tanggal selesai <span class="text-danger">*</span></label><input class="form-control" type="date" id="closed_at" name="closed_at" value="{{ old('closed_at', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required></div>
                <div class="col-12"><label class="form-label" for="final_result">Hasil akhir <span class="text-danger">*</span></label><textarea class="form-control" id="final_result" name="final_result" rows="4" required>{{ old('final_result') }}</textarea></div>
                <div class="col-12"><label class="form-label" for="resolution_summary">Ringkasan penyelesaian <span class="text-danger">*</span></label><textarea class="form-control" id="resolution_summary" name="resolution_summary" rows="4" required>{{ old('resolution_summary') }}</textarea></div>
                <div class="col-12"><label class="form-label" for="continued_plan">Rencana lanjutan</label><textarea class="form-control" id="continued_plan" name="continued_plan" rows="3">{{ old('continued_plan') }}</textarea></div>
                <div class="col-12 d-flex justify-content-end gap-2"><a href="{{ route('cases.show', $case) }}" class="btn btn-outline-secondary">Batal</a><button type="submit" class="btn btn-primary">Konfirmasi Penyelesaian</button></div>
            </form>
        </div>
    </div>
@endsection
