@extends('layouts.app-2')

@section('page-title', 'Data Master & Sinkronisasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-501">
        @if(session('success') || session('warning'))
            <div class="sibk-assignment-toast-region" aria-live="polite" aria-atomic="true">
                <div class="toast sibk-assignment-toast {{ session('warning') ? 'sibk-assignment-toast--warning' : '' }}" id="dataMasterToast" role="status">
                    <div class="toast-body d-flex align-items-start gap-2">
                        <span class="sibk-assignment-toast__icon" aria-hidden="true">
                            @if(session('warning'))
                                <svg viewBox="0 0 24 24"><path d="M12 7v6m0 4h.01" /></svg>
                            @else
                                <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6" /></svg>
                            @endif
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
                <h1 class="mb-1">Data Master</h1>
                <p class="mb-0">Siapkan tahun ajaran, impor murid, dan tinjau data dari sumber sekolah.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('data-master.classrooms.index') }}">Data Kelas</a>
        </div>

        <section class="sibk-panel mb-4" aria-labelledby="data-master-review-title">
            <div class="sibk-panel__body p-4">
                <h2 class="fs-6 fw-bold text-dark mb-2" id="data-master-review-title">Yang Perlu Ditinjau</h2>
                <div class="d-flex flex-wrap align-items-center gap-3 small">
                    @if($rolloverSummary?->needsConfirmationCount() > 0)
                        <a href="{{ route('data-master.index', ['tab' => 'dapodik']) }}#academic-year-rollover-title">
                            {{ $rolloverSummary->needsConfirmationCount() }} murid belum punya rombel di tahun baru
                        </a>
                    @endif
                    @if($unresolvedIssueCount > 0)
                        <a href="{{ route('data-master.etatib.conflicts.index') }}">{{ $unresolvedIssueCount }} data e-Tatib belum cocok</a>
                    @endif
                    @if($latestDapodikPreview)
                        <a href="{{ route('data-master.dapodik.previews.show', $latestDapodikPreview) }}">Tinjau data Dapodik sebelum diterapkan</a>
                    @endif
                    @if(! $latestDapodikPreview && $unresolvedIssueCount === 0 && ($rolloverSummary?->needsConfirmationCount() ?? 0) === 0)
                        <span class="text-muted">Belum ada data yang perlu ditinjau.</span>
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
                    <div class="row g-0">
                        <div class="col-12 col-xl-5 sibk-data-master-year">
                            @include('pages.data-master._academic-year-preparation')
                        </div>
                        <div class="col-12 col-xl-7 sibk-data-master-api">
                            @include('pages.data-master._api-siswa-import')
                        </div>
                    </div>
                    <div class="border-top mx-4"></div>
                    <div class="sibk-panel__body p-4">
                        <details @if($errors->has('file')) open @endif>
                            <summary class="fw-semibold text-primary py-2">Impor CSV (cadangan)</summary>
                            <p class="text-muted small mt-3">
                                Gunakan jika API Siswa belum tersedia. Siapkan CSV UTF-8 maksimal 2 MiB dan 5.000 baris,
                                dengan header <code>nisn,nama,rombel,tahun_pelajaran</code>.
                                Buat tahun ajarannya terlebih dahulu. Impor tambahan juga bisa dilakukan setelah tahun aktif.
                            </p>
                            <form action="{{ route('data-master.roster-imports.store') }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
                                @csrf
                                <div class="col-12 col-md">
                                    <label class="form-label small" for="roster_file">Pilih berkas CSV</label>
                                    <input class="form-control" type="file" accept=".csv,text/csv" id="roster_file" name="file" required>
                                </div>
                                <div class="col-12 col-md-auto">
                                    <button type="submit" class="btn btn-outline-primary w-100" @disabled($preparationYears->isEmpty())>Impor CSV</button>
                                </div>
                            </form>
                        </details>
                    </div>
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
