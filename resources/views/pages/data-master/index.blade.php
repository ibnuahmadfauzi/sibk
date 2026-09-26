@extends('layouts.app-2')

@section('page-title', 'Data Master & Sinkronisasi - Ruang BK')

@section('body')
    @php($notificationError = $errors->first('sync') ?: $errors->first('etatib_sync') ?: $errors->getBag('etatib_api')->first() ?: $errors->getBag('etatib_automatic')->first())
    <div class="sibk-dashboard" data-page-id="PG-501">
        @if(session('success') || session('warning') || $notificationError)
            <x-notification-toast :tone="$notificationError ? 'error' : (session('warning') ? 'warning' : 'success')">
                {{ $notificationError ?: (session('warning') ?? session('success')) }}
                @if(session('year_prepared'))
                    <a class="d-block mt-2" href="#api-siswa-import-title">Lanjut impor murid</a>
                @endif
            </x-notification-toast>
        @endif
        <!-- Header -->
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <h1 class="mb-1">Data Master</h1>
                <p class="mb-0">Siapkan tahun ajaran, impor murid, dan tinjau data dari sumber sekolah.</p>
            </div>
        </div>

        <nav class="nav nav-tabs sibk-data-master-tabs" aria-label="Bagian Data Master">
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
                <div class="sibk-panel sibk-data-master-tab-panel mb-4">
                    <div class="row g-0">
                        <div class="col-12 col-xl-5 sibk-data-master-year">
                            @include('pages.data-master._academic-year-preparation')
                        </div>
                        <div class="col-12 col-xl-7 sibk-data-master-api">
                            @include('pages.data-master._api-siswa-import')
                        </div>
                    </div>
                </div>
            </section>
        @else
            <section id="data-master-etatib" aria-label="e-Tatib">
                @include('pages.data-master._etatib-api-import')
            </section>
        @endif

        @if($rolloverSummary?->needsConfirmationCount() > 0 || $unresolvedIssueCount > 0 || $latestDapodikPreview)
            <section class="sibk-panel mb-4" aria-labelledby="data-master-review-title">
                <div class="sibk-panel__body p-4">
                    <h2 class="fs-6 fw-bold text-dark mb-2" id="data-master-review-title">Yang Perlu Ditinjau</h2>
                    <div class="d-flex flex-wrap align-items-center gap-3 small">
                        @if($rolloverSummary?->needsConfirmationCount() > 0)
                            <a href="{{ route('data-master.index', ['tab' => 'dapodik']) }}#academic-year-rollover-title">
                                {{ $rolloverSummary->needsConfirmationCount() }} murid tahun sebelumnya belum tercantum di {{ $rolloverTargetYear->name }}
                            </a>
                        @endif
                        @if($unresolvedIssueCount > 0)
                            <a href="{{ route('data-master.etatib.conflicts.index') }}">{{ $unresolvedIssueCount }} data e-Tatib belum cocok</a>
                        @endif
                        @if($latestDapodikPreview)
                            <a href="{{ route('data-master.dapodik.previews.show', $latestDapodikPreview) }}">Tinjau data Dapodik sebelum diterapkan</a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if($activeTab === 'dapodik')
            @include('pages.data-master._academic-year-rollover-exceptions')
        @endif

    </div>
@endsection
