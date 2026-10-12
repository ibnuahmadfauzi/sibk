@extends('layouts.app-2')

@section('page-title', 'Detail Konsultasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-103">
        @if(session('success'))
            <x-notification-toast>{{ session('success') }}</x-notification-toast>
        @endif

        <div class="sibk-page-header mb-4 d-flex flex-wrap justify-content-between gap-3">
            <div class="sibk-page-header__copy">
                <a href="{{ route('cases.index', ['tab' => 'konsultasi']) }}" class="text-decoration-none small">&larr; Kembali ke daftar</a>
                <h1 class="mb-1">Detail Konsultasi</h1>
                <p class="mb-0 text-muted">{{ $consultation->identityName() }} &mdash; {{ $consultation->session_date->locale('id')->translatedFormat('d F Y') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($canUpdateConsultation)
                    <a href="{{ route('consultations.edit', $consultation) }}" class="btn btn-primary">Edit</a>
                @endif
                @if($canArchiveConsultation)
                    <form action="{{ route('consultations.destroy', $consultation) }}" method="POST"
                        data-app-confirm-submit
                        data-confirm-title="Hapus Konsultasi?"
                        data-confirm-message="Apakah Anda yakin ingin menghapus konsultasi milik"
                        data-confirm-subject="{{ $consultation->identityName() }} ({{ $consultation->classroom?->name ?? 'Tanpa Rombel' }})"
                        data-confirm-suffix="?"
                        data-confirm-action="Hapus"
                        data-confirm-tone="danger">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit">Arsipkan</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="sibk-case-detail">
            @include('pages.consultations._detail-content')
        </div>
    </div>
@endsection
