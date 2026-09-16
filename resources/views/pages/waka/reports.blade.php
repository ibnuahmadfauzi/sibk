@extends('layouts.app-2')

@section('page-title', 'Laporan Waka Kesiswaan - Ruang BK')

@section('body')
<div class="sibk-dashboard" data-page-id="PG-WAKA-REPORTS">
    <header class="sibk-page-header mb-4">
        <div class="sibk-page-header__copy">
            <h1>Laporan Waka Kesiswaan</h1>
            <p>Pemantauan dan laporan bidang BK tingkat sekolah.</p>
        </div>
    </header>

    <div class="alert sibk-read-only-notice d-flex gap-3 align-items-start" role="status">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"/><path d="M12 16v-4M12 8h.01"/></svg>
        <div><strong>Laporan hanya-baca</strong><p class="mb-0">Portal menampilkan proyeksi aman tanpa catatan konseling sensitif atau tindakan perubahan.</p></div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert" tabindex="-1">
            <strong>Permintaan laporan belum dapat diproses.</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <nav class="mb-4" aria-label="Jenis laporan Waka">
        <div class="nav sibk-waka-tabs">
            @can('viewWakaMonitoring')
                @foreach(['penanganan' => 'Monitoring Penanganan', 'rekap' => 'Rekap Periode'] as $key => $label)
                    <a class="nav-link text-nowrap @if($tab === $key) active @endif" href="{{ route('waka.reports', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            @endcan
            @foreach(['laporan-akhir' => 'Laporan Akhir'] as $key => $label)
                <a class="nav-link text-nowrap @if($tab === $key) active @endif" href="{{ route('waka.reports', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </div>
    </nav>

    @if($tab === 'penanganan')
        @include('pages.waka._handling-report')
    @elseif($tab === 'rekap')
        @include('pages.waka._period-recap')
    @else
        @include('pages.waka._final-report-development')
    @endif
</div>
@endsection
