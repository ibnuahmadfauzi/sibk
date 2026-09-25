@extends('layouts.app-2')

@section('page-title', 'Data Master & Sinkronisasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-501">
        @if(session('success'))
            <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">
                <span>{{ session('success') }}</span>
                @if(session('year_prepared'))
                    <a class="btn btn-sm btn-outline-primary" href="#api-siswa-import-title">Lanjut impor murid</a>
                @endif
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
        @endif
        @error('sync')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror
        @error('etatib_sync')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror
        <!-- Header -->
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <h1 class="mb-1">Data Master dan Sinkronisasi</h1>
                <p class="mb-0">Siapkan tahun ajaran, impor murid, dan pantau sinkronisasi data.</p>
            </div>
        </div>

        <nav class="nav nav-tabs mb-4" aria-label="Bagian Data Master">
            @foreach([
                'dapodik' => 'Dapodik',
                'etatib' => 'e-Tatib',
            ] as $tab => $label)
                <a
                    class="nav-link {{ $activeTab === $tab ? 'active' : '' }}"
                    href="{{ route('data-master.index', ['tab' => $tab]) }}"
                    @if($activeTab === $tab) aria-current="page" @endif
                >{{ $label }}</a>
            @endforeach
        </nav>

        @if($activeTab === 'dapodik')
            <section id="data-master-dapodik" aria-label="Dapodik">
                @include('pages.data-master._academic-year-preparation')
                @include('pages.data-master._api-siswa-import')
                @include('pages.data-master._academic-year-rollover-exceptions')
                @if($latestDapodikPreview)
                    <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-3" role="status">
                        <div>
                            <strong>Pratinjau Dapodik menunggu penerapan.</strong>
                            Periksa hasil pencocokan sebelum data resmi diterapkan.
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('data-master.dapodik.previews.show', $latestDapodikPreview) }}">
                            Buka Pratinjau
                        </a>
                    </div>
                @endif
            </section>
        @else
            <section id="data-master-etatib" aria-label="e-Tatib">
                @include('pages.data-master._etatib-api-import')
            </section>
        @endif

        @if($activeTab === 'etatib')
        <div class="sibk-panel mb-4 border-0">
            <div class="sibk-panel__body p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h2 class="fs-6 fw-bold text-dark mb-1">Data yang Perlu Diperiksa</h2>
                        <p class="text-muted small mb-0">{{ $unresolvedIssueCount }} data e-Tatib belum cocok.</p>
                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('data-master.etatib.conflicts.index') }}">Kelola Konflik e-Tatib</a>
                </div>
            </div>
        </div>
        @endif

    </div>
@endsection
