@extends('layouts.app-2')

@section('page-title', 'Detail Konsultasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy"><a href="{{ route('cases.index', ['tab' => 'konsultasi']) }}" class="text-decoration-none small">&larr; Kembali ke daftar</a><h1>Detail Sesi Bimbingan</h1><p>Nomor Sesi: {{ $consultation->registration_number }}</p></div>
            <div class="d-flex gap-2"><button type="button" onclick="window.print()" class="btn btn-outline-secondary">Cetak Riwayat</button>@if($canUpdateConsultation)<a href="{{ route('consultations.edit', $consultation) }}" class="btn btn-primary">Edit Data Sesi</a>@endif</div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="sibk-panel h-100"><div class="sibk-panel__header p-4 border-bottom"><h2 class="fs-5 m-0">Informasi Murid & Jadwal</h2></div><div class="sibk-panel__body p-4">
                    <div class="p-3 bg-light rounded mb-4"><strong class="d-block">{{ $consultation->identityName() }}</strong><span class="small text-muted">NISN {{ $consultation->identityNisn() }}@if($consultation->temporary_student_id) &bull; Identitas sementara @endif</span></div>
                    <p><span class="text-muted small d-block">Status Sesi</span><span class="sibk-badge sibk-badge--primary">{{ $consultation->status->label }}</span></p>
                    <p><span class="text-muted small d-block">Jadwal Pelaksanaan</span><strong>{{ $consultation->session_date->locale('id')->translatedFormat('d F Y') }}</strong>@if($consultation->starts_at)<span class="small text-muted d-block">{{ substr($consultation->starts_at, 0, 5) }}@if($consultation->ends_at)–{{ substr($consultation->ends_at, 0, 5) }}@endif WIB</span>@endif</p>
                    <p><span class="text-muted small d-block">Guru BK Pencatat</span>{{ $consultation->counselor->name }}</p>
                    <p><span class="text-muted small d-block">Jenis Layanan</span>{{ $consultation->serviceField->label }}</p>
                    <p><span class="text-muted small d-block">Sumber Rujukan</span>{{ $consultation->referral_source ?: '—' }}</p>
                    <p><span class="text-muted small d-block">Kasus Terkait</span>@if($consultation->case)<a href="{{ route('cases.show', $consultation->case) }}">{{ $consultation->case->registration_number }}</a>@else — @endif</p>
                    <p class="mb-0"><span class="text-muted small d-block">Tindak Lanjut</span>{{ $consultation->follow_up_date?->locale('id')->translatedFormat('d F Y') ?? '—' }}</p>
                </div></div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="sibk-panel mb-4"><div class="sibk-panel__header p-4 border-bottom"><h2 class="fs-5 m-0">Ringkasan Umum</h2></div><div class="sibk-panel__body p-4">
                    <h3 class="fs-6 fw-bold">Topik / Permasalahan Awal</h3><p>{{ $consultation->topic }}</p>
                    <h3 class="fs-6 fw-bold">Ringkasan yang Diizinkan</h3><p class="mb-0">{{ $consultation->general_summary ?: 'Belum ada ringkasan umum.' }}</p>
                </div></div>

                @if($canViewSensitive)
                    <div class="sibk-panel"><div class="sibk-panel__header p-4 border-bottom"><h2 class="fs-5 m-0">Catatan Profesional Privat</h2></div><div class="sibk-panel__body p-4">
                        <h3 class="fs-6 fw-bold">Uraian / Proses Bimbingan</h3><p>{{ $consultation->privateNote?->sensitive_content ?: '—' }}</p>
                        <h3 class="fs-6 fw-bold">Catatan Internal</h3><p>{{ $consultation->privateNote?->internal_note ?: '—' }}</p>
                        <h3 class="fs-6 fw-bold">Kesimpulan / Solusi</h3><p>{{ $consultation->privateNote?->conclusion ?: '—' }}</p>
                        <h3 class="fs-6 fw-bold">Rencana Lanjutan</h3><p class="mb-0">{{ $consultation->privateNote?->follow_up_plan ?: '—' }}</p>
                    </div></div>
                @else
                    <div class="alert alert-info">Catatan profesional privat hanya tersedia bagi Guru BK dengan kewenangan atas murid.</div>
                @endif
            </div>
        </div>
    </div>
@endsection
