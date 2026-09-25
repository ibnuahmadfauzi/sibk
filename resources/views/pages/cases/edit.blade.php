@extends('layouts.app-2')

@section('page-title', 'Ubah Permasalahan - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-103-EDIT">
        <div class="sibk-page-header mb-4"><div class="d-flex align-items-center gap-3"><a href="{{ route('cases.show', $case) }}" class="btn btn-icon btn-light" aria-label="Kembali">&larr;</a><div class="sibk-page-header__copy m-0"><h1 class="mb-1">Ubah Permasalahan</h1><p class="mb-0">Perbarui catatan permasalahan.</p></div></div></div>
        <div class="sibk-panel"><div class="sibk-panel__body p-4">@include('pages.cases._edit-modal')</div></div>
    </div>
@endsection
