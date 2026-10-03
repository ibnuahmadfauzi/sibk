@extends('layouts.app-2')

@section('page-title', 'Detail Permasalahan - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-103">
        @if(session('success'))<x-notification-toast>{{ session('success') }}</x-notification-toast>@endif
        <div class="sibk-page-header mb-4 d-flex flex-wrap justify-content-between gap-3">
            <div class="sibk-page-header__copy"><a href="{{ route('cases.index') }}" class="text-decoration-none small">&larr; Kembali ke daftar</a><h1 class="mb-1">Detail Permasalahan</h1></div>
            <div class="d-flex gap-2">
                @if($canUpdateCase)<a href="{{ route('cases.edit', $case) }}"@if($case->status?->code === 'selesai') data-app-confirm data-confirm-title="Edit Permasalahan Selesai?" data-confirm-message="Permasalahan milik" data-confirm-subject="{{ $case->identityName() }} ({{ $case->classroom?->name ?? 'Tanpa Rombel' }})" data-confirm-suffix=" telah selesai. Apakah Anda ingin melanjutkan pengeditan?" data-confirm-action="Lanjutkan" data-confirm-tone="warning"@endif class="btn btn-primary">Ubah</a>@endif
                @if($canArchiveCase)<form action="{{ route('cases.destroy', $case) }}" method="POST" data-app-confirm-submit data-confirm-title="Hapus Kasus BK?" data-confirm-message="Apakah Anda yakin ingin menghapus kasus milik" data-confirm-subject="{{ $case->identityName() }} ({{ $case->classroom?->name ?? 'Tanpa Rombel' }})" data-confirm-suffix="?" data-confirm-action="Hapus" data-confirm-tone="danger">@csrf @method('DELETE')<button class="btn btn-outline-danger" type="submit">Hapus</button></form>@endif
            </div>
        </div>
        <div class="sibk-panel"><div class="sibk-panel__body p-4">@include('pages.cases._detail-modal')</div></div>
    </div>
@endsection
