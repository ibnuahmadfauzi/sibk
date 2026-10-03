@extends('layouts.app-2')

@section('page-title', 'Edit Pengunduran Diri - Ruang BK')

@section('body')
    <div class="sibk-dashboard py-3">
        <div class="d-flex align-items-center gap-3 mb-4">
            <x-back-button :href="route('cases.index', ['tab' => 'pengunduran-diri'])" label="Kembali ke daftar pengunduran diri" />
            <div><h1 class="h4 fw-bold mb-1 text-dark">Edit Penanganan Pengunduran Diri</h1><p class="text-secondary small mb-0">Perbarui tanggal pencatatan dan catatan pengunduran diri murid.</p></div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4" role="alert"><div class="fw-semibold mb-1">Terdapat kesalahan pengisian data:</div><ul class="mb-0 ps-4 small">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('withdrawals.update', $withdrawal) }}">
            @csrf
            @method('PATCH')

            <section class="card border-0 shadow-sm rounded-4 mb-4" aria-labelledby="withdrawal-edit-identity-title"><div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center gap-3 mb-4"><span class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px">1</span><div><h2 class="h5 fw-bold mb-0" id="withdrawal-edit-identity-title">Identitas Murid</h2><p class="small text-muted mb-0 mt-1">Murid tidak dapat diganti pada penanganan yang sudah dicatat.</p></div></div>
                <div class="row g-3">
                    <div class="col-12 col-md-4"><label class="form-label fw-semibold" for="withdrawal-edit-name">Nama Murid</label><input class="form-control bg-light" id="withdrawal-edit-name" value="{{ $withdrawal->student?->name ?? 'Murid tidak tersedia' }}" readonly></div>
                    <div class="col-12 col-md-4"><label class="form-label fw-semibold" for="withdrawal-edit-nisn">NISN</label><input class="form-control bg-light" id="withdrawal-edit-nisn" value="{{ $withdrawal->student?->nisn ?? '-' }}" readonly></div>
                    <div class="col-12 col-md-4"><label class="form-label fw-semibold" for="withdrawal-edit-classroom">Rombel/Kelas</label><input class="form-control bg-light" id="withdrawal-edit-classroom" value="{{ $withdrawal->classroom?->name ?? 'Kelas belum tercatat' }}" readonly></div>
                    <div class="col-12 col-md-4"><label class="form-label fw-semibold" for="withdrawal-edit-recorded-on">Tanggal Pencatatan <span class="text-danger">*</span></label><input class="form-control" id="withdrawal-edit-recorded-on" name="recorded_on" type="date" value="{{ old('recorded_on', $withdrawal->recorded_on->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
                </div>
            </div></section>

            <section class="card border-0 shadow-sm rounded-4" aria-labelledby="withdrawal-edit-note-title"><div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center gap-3 mb-4"><span class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px">2</span><div><h2 class="h5 fw-bold mb-0" id="withdrawal-edit-note-title">Catatan Pengunduran Diri</h2><p class="small text-muted mb-0 mt-1">Catatan ini tidak menetapkan murid resmi keluar.</p></div></div>
                <div class="mb-3"><label class="form-label fw-semibold" for="withdrawal-edit-note">Catatan <span class="text-danger">*</span></label><textarea class="form-control" id="withdrawal-edit-note" name="note" rows="4" maxlength="2000" required>{{ old('note', $withdrawal->note ?? $withdrawal->reason) }}</textarea></div>
            </div></section>

            <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 mt-4"><a class="btn btn-light border" href="{{ route('cases.index', ['tab' => 'pengunduran-diri']) }}">Batal</a><button class="btn btn-primary" type="submit">Simpan Perubahan</button></div>
        </form>
    </div>
@endsection
