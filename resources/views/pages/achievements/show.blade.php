@extends('layouts.app-2')

@section('page-title', 'Detail Prestasi - Ruang BK')

@section('body')
<div class="sibk-dashboard">
    <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div class="sibk-page-header__copy"><a href="{{ route('achievements.index') }}" class="text-decoration-none small">&larr; Daftar Prestasi</a><h1>Detail Prestasi</h1><p>Riwayat prestasi murid.</p></div>
        @if($canUpdateAchievement)<a href="{{ route('achievements.edit', $achievement) }}" class="btn btn-primary">Edit Prestasi</a>@endif
    </div>
    <div class="sibk-panel"><div class="sibk-panel__body p-4">
        <h2 class="fs-4 mb-1">{{ $achievement->activity_name }}</h2>
        <p class="text-muted">{{ $achievement->student->name }} &mdash; NISN {{ $achievement->student->nisn }}</p>
        <dl class="row mb-0">
            <dt class="col-sm-4">Jenis / Tingkat</dt><dd class="col-sm-8">{{ $achievement->type->label }} / {{ $achievement->level->label }}</dd>
            <dt class="col-sm-4">Penyelenggara</dt><dd class="col-sm-8">{{ $achievement->organizer }}</dd>
            <dt class="col-sm-4">Tanggal</dt><dd class="col-sm-8">{{ $achievement->achievement_date->locale('id')->translatedFormat('d F Y') }}</dd>
            <dt class="col-sm-4">Hasil</dt><dd class="col-sm-8">{{ $achievement->result }}</dd>
            <dt class="col-sm-4">Pencatat</dt><dd class="col-sm-8">{{ $achievement->recorder->name }}</dd>
        </dl>
    </div></div>
</div>
@endsection
