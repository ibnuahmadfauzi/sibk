@extends('layouts.app-2')

@section('page-title', ($isEdit ? 'Ubah' : 'Catat').' Konsultasi - Ruang BK')

@section('body')
    @php
        $userDisplayName = auth()->user()?->name ?? 'Guru BK';
    @endphp

    <div class="sibk-dashboard py-3" data-page-id="PG-105">
        {{-- Header Halaman --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                @if($isEdit)
                    <x-back-button :href="route('consultations.show', $consultation)" label="Kembali ke detail konsultasi" />
                @else
                    <x-back-button :href="route('cases.index', ['tab' => 'konsultasi'])" label="Kembali ke daftar konsultasi" />
                @endif
                <div>
                    <h1 class="h4 fw-bold mb-1 text-dark">{{ $isEdit ? 'Ubah' : 'Catat' }} Konsultasi</h1>
                    <p class="text-secondary small mb-0">Konsultasi dicatat sebagai layanan yang telah selesai.</p>
                </div>
            </div>

            {{-- User Profile Badge --}}
            <div class="d-none d-md-flex align-items-center gap-2 px-3 py-2 bg-white rounded-pill border shadow-sm">
                <span class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary" style="width: 30px; height: 30px;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </span>
                <span class="small fw-semibold text-dark">{{ $userDisplayName }}</span>
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-muted ms-1" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>

        @if($isEdit)
            <div class="sibk-panel"><div class="sibk-panel__body p-4">@include('pages.consultations._edit-content', ['modal' => false])</div></div>
        @else
            @include('pages.consultations._create-modal', ['modal' => false])
        @endif
    </div>
@endsection
