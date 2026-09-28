@extends('layouts.app-2')

@section('page-title', ($isEdit ? 'Edit' : 'Catat').' Prestasi - Ruang BK')

@section('body')
<div class="sibk-dashboard" data-page-id="PG-203">
    <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3"><a href="{{ $isEdit ? route('achievements.show', $achievement) : route('achievements.index') }}" class="btn btn-icon btn-light" aria-label="Kembali">&larr;</a><div class="sibk-page-header__copy m-0"><h1 class="mb-1">{{ $isEdit ? 'Edit Prestasi' : 'Catat Prestasi' }}</h1><p class="mb-0">{{ $isEdit ? 'Perbarui riwayat prestasi murid.' : 'Tambahkan riwayat prestasi murid.' }}</p></div></div>
    </div>
    @if($errors->any())<div class="alert alert-danger"><strong>Periksa kembali data berikut:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form action="{{ $isEdit ? route('achievements.update', $achievement) : route('achievements.store') }}" method="POST" class="d-flex flex-column gap-4" data-autosave-form="achievement" data-autosave-record="{{ $achievement?->id ?? 'new' }}">
        @if($isEdit)<input type="hidden" name="expected_updated_at" value="{{ old('expected_updated_at', $achievement->updated_at?->toJSON()) }}">@endif
        @csrf @if($isEdit) @method('PATCH') @endif
        <div class="sibk-panel"><div class="sibk-panel__body p-4 p-md-5"><h2 class="sibk-section-title mb-4">Informasi Prestasi</h2><div class="row g-3">
            <div class="col-12 col-md-6 col-lg-5"><label for="student_id" class="form-label sibk-form-label">Murid <span class="text-danger">*</span></label>@if($isEdit)<input class="form-control" value="{{ $achievement->student->name }} — {{ $achievement->student->nisn }}" disabled>@else<select class="form-select sibk-form-select" id="student_id" name="student_id" required><option value="">Pilih murid</option>@foreach($students as $student)@php($membership = $student->classMemberships->first())<option value="{{ $student->id }}" @selected((string) old('student_id', $preselectedStudentId) === (string) $student->id)>{{ $student->name }} — {{ $student->nisn }}{{ $membership?->classroom ? ' · '.$membership->classroom->name : '' }}</option>@endforeach</select>@endif</div>
            <div class="col-12 col-md-3 col-lg-4"><label for="type_id" class="form-label sibk-form-label">Jenis Prestasi <span class="text-danger">*</span></label><select class="form-select sibk-form-select" id="type_id" name="type_id" required><option value="">Pilih jenis prestasi</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected((string) old('type_id', $achievement?->type_id) === (string) $type->id)>{{ $type->label }}</option>@endforeach</select></div>
            <div class="col-12 col-md-3"><label for="level_id" class="form-label sibk-form-label">Tingkat <span class="text-danger">*</span></label><select class="form-select sibk-form-select" id="level_id" name="level_id" required><option value="">Pilih tingkat</option>@foreach($levels as $level)<option value="{{ $level->id }}" @selected((string) old('level_id', $achievement?->level_id) === (string) $level->id)>{{ $level->label }}</option>@endforeach</select></div>
            <div class="col-12 col-md-5"><label for="activity_name" class="form-label sibk-form-label">Nama Kegiatan <span class="text-danger">*</span></label><input type="text" class="form-control sibk-form-control" id="activity_name" name="activity_name" maxlength="250" value="{{ old('activity_name', $achievement?->activity_name) }}" required></div>
            <div class="col-12 col-md-4"><label for="organizer" class="form-label sibk-form-label">Penyelenggara <span class="text-danger">*</span></label><input type="text" class="form-control sibk-form-control" id="organizer" name="organizer" maxlength="200" value="{{ old('organizer', $achievement?->organizer) }}" required></div>
            <div class="col-12 col-md-3"><label for="achievement_date" class="form-label sibk-form-label">Tanggal <span class="text-danger">*</span></label><input type="date" class="form-control sibk-form-control" id="achievement_date" name="achievement_date" max="{{ today()->toDateString() }}" value="{{ old('achievement_date', $achievement?->achievement_date?->toDateString()) }}" required></div>
        </div></div></div>
        <div class="sibk-panel"><div class="sibk-panel__body p-4 p-md-5"><h2 class="sibk-section-title mb-4">Hasil Prestasi</h2><div class="row g-3">
            <div class="col-12 col-md-6"><label for="result" class="form-label sibk-form-label">Hasil atau Peringkat <span class="text-danger">*</span></label><input type="text" class="form-control sibk-form-control" id="result" name="result" maxlength="250" value="{{ old('result', $achievement?->result) }}" required></div>
        </div></div></div>
        <div class="d-flex justify-content-end gap-2 pb-4"><span class="small text-muted me-auto align-self-center" data-draft-status aria-live="polite"></span><button type="button" class="btn btn-light" data-clear-draft>Hapus Draft</button><a href="{{ $isEdit ? route('achievements.show', $achievement) : route('achievements.index') }}" class="btn btn-light">Batal</a><button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Prestasi' }}</button></div>
    </form>
</div>
@endsection
