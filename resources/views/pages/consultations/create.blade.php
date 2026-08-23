@extends('layouts.app-2')

@section('page-title', ($isEdit ? 'Ubah' : 'Catat').' Konsultasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-105">
        <div class="sibk-page-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ $isEdit ? route('consultations.show', $consultation) : route('cases.index', ['tab' => 'konsultasi']) }}" class="btn btn-icon btn-light" aria-label="Kembali">&larr;</a>
                <div class="sibk-page-header__copy m-0"><h1>{{ $isEdit ? 'Ubah' : 'Catat' }} Konsultasi</h1><p>Metadata, ringkasan umum, dan catatan privat disimpan terpisah.</p></div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ $isEdit ? route('consultations.update', $consultation) : route('consultations.store') }}" method="POST">
            @csrf
            @if($isEdit) @method('PATCH') @endif

            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Murid dan Konteks Layanan</h2></div>
                <div class="sibk-panel__body p-4 row g-4">
                    @if($isEdit)
                        <div class="col-12"><label class="form-label">Murid</label><div class="form-control bg-light">{{ $consultation->identityName() }} — NISN {{ $consultation->identityNisn() }}</div></div>
                    @else
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="student_id">Murid Master</label>
                            <select class="form-select" id="student_id" name="student_id">
                                <option value="">Pilih murid atau isi identitas sementara</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" data-nisn="{{ $student->nisn }}" @selected((string) old('student_id', $preselectedStudentId) === (string) $student->id)>{{ $student->name }} — {{ $student->nisn }} ({{ $student->classMemberships->first()?->classroom?->name ?? 'Tanpa kelas aktif' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3"><label class="form-label" for="temporary_nisn">NISN Sementara</label><input class="form-control" id="temporary_nisn" name="temporary_nisn" value="{{ old('temporary_nisn') }}" inputmode="numeric" maxlength="20"></div>
                        <div class="col-12 col-md-3"><label class="form-label" for="temporary_name">Nama Sementara</label><input class="form-control" id="temporary_name" name="temporary_name" value="{{ old('temporary_name') }}" maxlength="150"></div>
                    @endif

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="case_id">Kasus Terkait</label>
                        <select class="form-select" id="case_id" name="case_id">
                            <option value="">Tidak terkait kasus khusus</option>
                            @foreach($cases as $case)
                                <option value="{{ $case->id }}" data-nisn="{{ $case->identityNisn() }}" @selected((string) old('case_id', $consultation?->case_id) === (string) $case->id)>{{ $case->registration_number }} — {{ $case->identityName() }} ({{ $case->status->label }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="service_field_id">Jenis Layanan <span class="text-danger">*</span></label>
                        <select class="form-select" id="service_field_id" name="service_field_id" required><option value="">Pilih jenis</option>@foreach($serviceFields as $field)<option value="{{ $field->id }}" @selected((string) old('service_field_id', $consultation?->service_field_id) === (string) $field->id)>{{ $field->label }}</option>@endforeach</select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="status_id">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status_id" name="status_id" required><option value="">Pilih status</option>@foreach($consultationStatuses as $status)<option value="{{ $status->id }}" @selected((string) old('status_id', $consultation?->status_id) === (string) $status->id)>{{ $status->label }}</option>@endforeach</select>
                    </div>
                    <div class="col-12 col-md-8"><label class="form-label" for="topic">Topik / Permasalahan Awal <span class="text-danger">*</span></label><input class="form-control" id="topic" name="topic" value="{{ old('topic', $consultation?->topic) }}" maxlength="250" required></div>
                    <div class="col-12 col-md-4"><label class="form-label" for="referral_source">Sumber Rujukan</label><input class="form-control" id="referral_source" name="referral_source" value="{{ old('referral_source', $consultation?->referral_source) }}" maxlength="150"></div>
                </div>
            </div>

            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Jadwal dan Ringkasan Umum</h2></div>
                <div class="sibk-panel__body p-4 row g-4">
                    <div class="col-12 col-md-4"><label class="form-label" for="session_date">Tanggal Sesi <span class="text-danger">*</span></label><input type="date" class="form-control" id="session_date" name="session_date" value="{{ old('session_date', $consultation?->session_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}" required></div>
                    <div class="col-6 col-md-2"><label class="form-label" for="starts_at">Jam Mulai</label><input type="time" class="form-control" id="starts_at" name="starts_at" value="{{ old('starts_at', $consultation?->starts_at ? substr($consultation->starts_at, 0, 5) : null) }}"></div>
                    <div class="col-6 col-md-2"><label class="form-label" for="ends_at">Jam Selesai</label><input type="time" class="form-control" id="ends_at" name="ends_at" value="{{ old('ends_at', $consultation?->ends_at ? substr($consultation->ends_at, 0, 5) : null) }}"></div>
                    <div class="col-12 col-md-4"><label class="form-label" for="follow_up_date">Jadwal Tindak Lanjut</label><input type="date" class="form-control" id="follow_up_date" name="follow_up_date" value="{{ old('follow_up_date', $consultation?->follow_up_date?->format('Y-m-d')) }}"></div>
                    <div class="col-12"><label class="form-label" for="general_summary">Ringkasan Umum</label><textarea class="form-control" id="general_summary" name="general_summary" rows="4" placeholder="Ringkasan yang diizinkan untuk tata kelola umum">{{ old('general_summary', $consultation?->general_summary) }}</textarea><div class="form-text">Wajib untuk sesi berstatus Terlaksana.</div></div>
                </div>
            </div>

            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Catatan Profesional Privat</h2><p class="sibk-panel__subtitle">Bagian ini hanya dapat dibaca Guru BK dengan kewenangan profesional yang sah.</p></div>
                <div class="sibk-panel__body p-4 row g-4">
                    <div class="col-12"><label class="form-label" for="sensitive_content">Uraian / Proses Bimbingan</label><textarea class="form-control" id="sensitive_content" name="sensitive_content" rows="5">{{ old('sensitive_content', $consultation?->privateNote?->sensitive_content) }}</textarea></div>
                    <div class="col-12 col-md-6"><label class="form-label" for="internal_note">Catatan Internal</label><textarea class="form-control" id="internal_note" name="internal_note" rows="4">{{ old('internal_note', $consultation?->privateNote?->internal_note) }}</textarea></div>
                    <div class="col-12 col-md-6"><label class="form-label" for="conclusion">Kesimpulan / Solusi</label><textarea class="form-control" id="conclusion" name="conclusion" rows="4">{{ old('conclusion', $consultation?->privateNote?->conclusion) }}</textarea></div>
                    <div class="col-12"><label class="form-label" for="follow_up_plan">Rencana Lanjutan</label><textarea class="form-control" id="follow_up_plan" name="follow_up_plan" rows="3">{{ old('follow_up_plan', $consultation?->privateNote?->follow_up_plan) }}</textarea></div>
                </div>
            </div>

            <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><h2 class="fs-6 fw-bold">Dokumen Pendukung</h2><p class="text-muted mb-0">Unggahan dokumen belum tersedia sampai kebijakan format, akses, dan retensi DEP-06 disahkan.</p></div></div>

            <div class="d-flex justify-content-end gap-2 mb-5"><a href="{{ $isEdit ? route('consultations.show', $consultation) : route('cases.index', ['tab' => 'konsultasi']) }}" class="btn btn-outline-secondary">Batal</a><button class="btn btn-primary" type="submit">Simpan Konsultasi</button></div>
        </form>
    </div>
@endsection

@section('extra-javascript')
    @if(!$isEdit)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const student = document.getElementById('student_id');
                const nisn = document.getElementById('temporary_nisn');
                const name = document.getElementById('temporary_name');
                student?.addEventListener('change', () => { if (student.value) { nisn.value = ''; name.value = ''; } });
                nisn?.addEventListener('input', () => { if (nisn.value.trim()) student.value = ''; });
            });
        </script>
    @endif
@endsection
