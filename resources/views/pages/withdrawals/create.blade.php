@extends('layouts.app-2')

@section('page-title', 'Catat Pengunduran Diri - Ruang BK')

@section('body')
    <div class="sibk-dashboard py-3" data-withdrawal-create-page data-withdrawal-students="{{ $withdrawalLookup->toJson() }}">
        <div class="d-flex align-items-center gap-3 mb-4">
            <x-back-button :href="route('cases.index', ['tab' => 'pengunduran-diri'])" label="Kembali ke daftar pengunduran diri" />
            <div><h1 class="h4 fw-bold mb-1 text-dark">Catat Pengunduran Diri</h1><p class="text-secondary small mb-0">Catat penanganan awal pengunduran diri murid dalam scope Anda.</p></div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4" role="alert"><div class="fw-semibold mb-1">Terdapat kesalahan pengisian data:</div><ul class="mb-0 ps-4 small">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        @if($withdrawalStudents->isEmpty())
            <div class="sibk-panel"><div class="sibk-panel__body p-4 p-md-5 text-center"><h2 class="h5 fw-bold">Semua murid sudah memiliki catatan</h2><p class="text-muted mb-4">Tidak ada murid dalam penugasan aktif Anda yang dapat dicatat kembali.</p><a class="btn btn-outline-primary" href="{{ route('cases.index', ['tab' => 'pengunduran-diri']) }}">Kembali ke daftar</a></div></div>
        @else
            <form method="POST" action="{{ route('withdrawals.store') }}" data-withdrawal-create-form>
                @csrf
                <input type="hidden" name="student_id" id="withdrawal-student-id" value="{{ old('student_id') }}">

                <section class="card border-0 shadow-sm rounded-4 mb-4" aria-labelledby="withdrawal-student-section-title"><div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center gap-3 mb-4"><span class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px">1</span><div><h2 class="h5 fw-bold mb-0" id="withdrawal-student-section-title">Murid dan Tanggal</h2><p class="small text-muted mb-0 mt-1">Pilih murid dari daftar penugasan aktif Anda.</p></div></div>
                    <div class="row g-3">
                        <div class="col-12 col-lg-7 position-relative"><label class="form-label fw-semibold" for="withdrawal-student-lookup">Cari nama atau NISN <span class="text-danger">*</span></label><input class="form-control" id="withdrawal-student-lookup" type="search" autocomplete="off" placeholder="Ketik nama atau NISN murid" aria-controls="withdrawal-student-options" aria-autocomplete="list" aria-expanded="false" required><div class="list-group position-absolute start-0 end-0 mx-3 mt-1 shadow-sm d-none withdrawal-student-options" id="withdrawal-student-options" role="listbox"></div></div>
                        <div class="col-12 col-lg-5"><label class="form-label fw-semibold" for="withdrawal-recorded-on">Tanggal Pencatatan <span class="text-danger">*</span></label><input class="form-control" id="withdrawal-recorded-on" name="recorded_on" type="date" value="{{ old('recorded_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
                    </div>
                </div></section>

                <section class="card border-0 shadow-sm rounded-4" aria-labelledby="withdrawal-note-section-title"><div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center gap-3 mb-4"><span class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px">2</span><div><h2 class="h5 fw-bold mb-0" id="withdrawal-note-section-title">Catatan Pengunduran Diri</h2><p class="small text-muted mb-0 mt-1">Catatan ini tidak menetapkan murid resmi keluar.</p></div></div>
                    <div class="mb-3"><label class="form-label fw-semibold" for="withdrawal-note">Catatan <span class="text-danger">*</span></label><textarea class="form-control" id="withdrawal-note" name="note" rows="4" maxlength="2000" required>{{ old('note') }}</textarea></div>
                    <div class="alert alert-info mb-0"><span class="fw-semibold">Progres awal:</span> {{ \App\Models\WithdrawalProgress::labels()[\App\Models\WithdrawalProgress::PROGRESS_IN_PROGRESS] }}</div>
                </div></section>

                <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 mt-4"><a class="btn btn-light border" href="{{ route('cases.index', ['tab' => 'pengunduran-diri']) }}">Batal</a><button class="btn btn-primary" type="submit" data-withdrawal-submit>Simpan</button></div>
            </form>
        @endif
    </div>
@endsection
