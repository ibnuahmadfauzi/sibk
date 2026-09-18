@extends('layouts.app-2')

@section('page-title', 'Detail Kasus - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-103">
        @if(session('success'))<div class="alert alert-success" role="alert">{{ session('success') }}</div>@endif
        <div class="sibk-page-header mb-4 d-flex flex-wrap justify-content-between gap-3">
            <div class="sibk-page-header__copy"><a href="{{ route('cases.index') }}" class="text-decoration-none small">&larr; Kembali ke daftar</a><h1 class="mb-1">Detail Kasus</h1></div>
            <div class="d-flex gap-2">
                @if($canUpdateCase)<a href="{{ route('cases.edit', $case) }}" class="btn btn-primary">Ubah Kasus</a>@endif
                @if($canArchiveCase)<form action="{{ route('cases.destroy', $case) }}" method="POST" data-confirm-submit data-confirm-message="Data akan diarsipkan dan tidak tampil pada daftar utama. Lanjutkan?">@csrf @method('DELETE')<button class="btn btn-outline-danger" type="submit">Hapus</button></form>@endif
            </div>
        </div>
        <div class="sibk-panel"><div class="sibk-panel__body p-4">@include('pages.cases._detail-modal')</div></div>
    </div>
@endsection
