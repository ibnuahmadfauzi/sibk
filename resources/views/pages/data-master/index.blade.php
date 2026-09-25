@extends('layouts.app-2')

@section('page-title', 'Data Master & Sinkronisasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-501">
        @if(session('success') || session('warning'))
            <div class="sibk-assignment-toast-region" aria-live="polite" aria-atomic="true">
                <div class="toast sibk-assignment-toast {{ session('warning') ? 'sibk-assignment-toast--warning' : '' }}" id="dataMasterToast" role="status">
                    <div class="toast-body d-flex align-items-start gap-2">
                        <span class="sibk-assignment-toast__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6" /></svg>
                        </span>
                        <div class="flex-grow-1">
                            <strong class="d-block">{{ session('warning') ? 'Perlu perhatian' : 'Perubahan berhasil' }}</strong>
                            <span>{{ session('warning') ?? session('success') }}</span>
                            @if(session('year_prepared'))
                                <a class="d-block mt-2" href="#api-siswa-import-title">Lanjut impor murid</a>
                            @endif
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="toast" aria-label="Tutup pemberitahuan"></button>
                    </div>
                </div>
            </div>
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
                <p class="mb-0">Siapkan tahun ajaran dan periksa data murid dari sumber sekolah.</p>
            </div>
        </div>

        <section class="sibk-panel mb-4" aria-labelledby="data-master-review-title">
            <div class="sibk-panel__body p-4">
                <h2 class="fs-6 fw-bold text-dark mb-2" id="data-master-review-title">Data yang Perlu Diperiksa</h2>
                <div class="d-flex flex-wrap align-items-center gap-3 small">
                    <span><strong>{{ $rolloverSummary?->needsConfirmationCount() ?? 0 }}</strong> murid tanpa penempatan tahun target</span>
                    <span><strong>{{ $unresolvedIssueCount }}</strong> konflik e-Tatib</span>
                    @if($latestDapodikPreview)
                        <a href="{{ route('data-master.dapodik.previews.show', $latestDapodikPreview) }}">Pratinjau Dapodik menunggu penerapan</a>
                    @endif
                    @if($rolloverSummary?->needsConfirmationCount() > 0)
                        <a href="{{ route('data-master.index', ['tab' => 'dapodik']) }}#academic-year-rollover-title">Lihat murid</a>
                    @endif
                    @if($unresolvedIssueCount > 0)
                        <a href="{{ route('data-master.etatib.conflicts.index') }}">Periksa konflik</a>
                    @endif
                </div>
            </div>
        </section>

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
                <div class="sibk-panel mb-4">
                    @include('pages.data-master._academic-year-preparation')
                    <div class="border-top mx-4"></div>
                    @include('pages.data-master._api-siswa-import')
                </div>
                @include('pages.data-master._academic-year-rollover-exceptions')
            </section>
        @else
            <section id="data-master-etatib" aria-label="e-Tatib">
                @include('pages.data-master._etatib-api-import')
            </section>
        @endif

    </div>
@endsection
