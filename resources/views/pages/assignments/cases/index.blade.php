@extends('layouts.app-2')

@section('page-title', 'Penugasan & Pengalihan Kasus - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-403">
        <div class="sibk-page-header mb-4"><div class="sibk-page-header__copy"><h1>Penugasan dan Pengalihan Kasus</h1><p>Atur penanggung jawab atau kewenangan tambahan untuk kasus aktif.</p></div></div>
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="sibk-panel mb-4">
            <form action="{{ route('assignments.cases.index') }}" method="GET" class="row g-3 p-4 align-items-end">
                <div class="col-12 col-lg-9">
                    <label class="form-label" for="case_id">Kasus target</label>
                    <select class="form-select" id="case_id" name="case_id" required>
                        @forelse($cases as $caseOption)
                            <option value="{{ $caseOption->id }}" @selected($selectedCase?->is($caseOption))>{{ $caseOption->registration_number }} — {{ $caseOption->identityName() }} ({{ $caseOption->status->label }})</option>
                        @empty
                            <option value="">Tidak ada kasus aktif</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-12 col-lg-3"><button class="btn btn-outline-primary w-100" type="submit">Pilih Kasus</button></div>
            </form>
        </div>

        @if($selectedCase)
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Kasus Terpilih</h2></div>
                <div class="sibk-panel__body p-4 pt-2">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div><strong class="text-primary">{{ $selectedCase->registration_number }}</strong><span class="mx-2">&bull;</span>{{ $selectedCase->identityName() }}<span class="mx-2">&bull;</span>NISN {{ $selectedCase->identityNisn() }}</div>
                        <span class="sibk-badge sibk-badge--primary">{{ $selectedCase->status->label }}</span>
                    </div>
                    <div class="mt-3 small text-muted">
                        Penugasan saat ini:
                        @forelse($selectedCase->assignments->sortByDesc('effective_from') as $assignment)
                            <span class="d-block">{{ $assignment->teacher->name }} — {{ $assignment->assignment_type === 'owner' ? 'Penanggung jawab' : 'Kewenangan tambahan' }} ({{ $assignment->effective_from->format('d-m-Y') }} s.d. {{ $assignment->effective_until?->format('d-m-Y') ?? 'sekarang' }})</span>
                        @empty
                            <span>belum tersedia.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="sibk-panel">
                <form action="{{ route('cases.assign', $selectedCase) }}" method="POST" class="row g-4 p-4">
                    @csrf
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="assignment_type">Jenis perubahan <span class="text-danger">*</span></label>
                        <select class="form-select" id="assignment_type" name="assignment_type" required>
                            <option value="transfer" @selected(old('assignment_type') === 'transfer')>Pengalihan penanggung jawab</option>
                            <option value="additional" @selected(old('assignment_type') === 'additional')>Kewenangan tambahan</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="to_user_id">Guru BK penerima <span class="text-danger">*</span></label>
                        <select class="form-select" id="to_user_id" name="to_user_id" required>
                            <option value="">Pilih Guru BK</option>
                            @foreach($counselors as $counselor)<option value="{{ $counselor->id }}" @selected((string) old('to_user_id') === (string) $counselor->id)>{{ $counselor->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4"><label class="form-label" for="effective_date">Tanggal berlaku <span class="text-danger">*</span></label><input class="form-control" type="date" id="effective_date" name="effective_date" value="{{ old('effective_date', now()->format('Y-m-d')) }}" required></div>
                    <div class="col-12"><label class="form-label" for="reason">Alasan <span class="text-danger">*</span></label><textarea class="form-control" id="reason" name="reason" rows="3" required>{{ old('reason') }}</textarea></div>
                    <div class="col-12 d-flex justify-content-end gap-2"><a href="{{ route('cases.show', $selectedCase) }}" class="btn btn-outline-secondary">Batal</a><button class="btn btn-primary" type="submit">Simpan Penugasan</button></div>
                </form>
            </div>
        @else
            <div class="sibk-panel p-5 text-center text-muted">Tidak ada kasus aktif yang dapat ditugaskan.</div>
        @endif
    </div>
@endsection
