@extends('layouts.app-2')

@section('page-title', 'Notifikasi - Ruang BK')

@section('body')
<div class="container-fluid py-4 px-lg-4">

    {{-- Header --}}
    <div class="sibk-notification-header">
        <h1 class="sibk-page-title">Notifikasi</h1>
        <p class="sibk-page-subtitle text-muted">Pemberitahuan terkait layanan dan tindak lanjut.</p>
    </div>

    {{-- Tabs --}}
    <div class="sibk-notification-tabs">
        <button class="btn btn-primary" type="button">Semua</button>
        <button class="btn btn-outline-secondary" type="button">Belum dibaca</button>
    </div>

    {{-- Main Panel --}}
    <div class="sibk-panel">
        <div class="sibk-panel__header">
            <div class="sibk-panel__title-group">
                <h2>Daftar Notifikasi</h2>
                <span class="badge bg-secondary ms-2" style="font-weight: 500;">{{ count($notifications) }}</span>
            </div>
            <button class="btn btn-link text-decoration-none" style="font-size: 0.85rem; font-weight: 500;">
                Tandai semua dibaca
            </button>
        </div>
        
        <div class="sibk-notification-list">
            @forelse($notifications as $notif)
                <a href="{{ route('cases.index') }}" class="sibk-notification-item {{ !$notif['is_read'] ? 'is-unread' : '' }}">
                    <div class="sibk-notification-item__icon-col">
                        <div class="sibk-notification-item__icon sibk-icon-tone--{{ $notif['tone'] ?? 'primary' }}">
                            <svg viewBox="0 0 24 24">{!! $notif['icon'] ?? '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/>' !!}</svg>
                        </div>
                    </div>
                    <div class="sibk-notification-item__content">
                        <div class="sibk-notification-item__header">
                            <p class="sibk-notification-item__title">{{ $notif['title'] }}</p>
                            <div class="sibk-notification-item__meta">
                                <span class="sibk-notification-item__time">{{ $notif['time'] }}</span>
                                @if(!$notif['is_read'])
                                    <span class="sibk-notification-item__unread-dot"></span>
                                @endif
                            </div>
                        </div>
                        <p class="sibk-notification-item__desc">{{ $notif['description'] }}</p>
                    </div>
                </a>
            @empty
                <div class="text-center py-5 text-muted">
                    <p class="mb-0">Tidak ada notifikasi baru.</p>
                </div>
            @endforelse
        </div>
        
        <div class="sibk-panel__footer border-top px-4 py-3 text-center">
            <span class="text-muted" style="font-size: 0.85rem;">{{ count($notifications) }} notifikasi ditampilkan</span>
        </div>
    </div>
</div>
@endsection
