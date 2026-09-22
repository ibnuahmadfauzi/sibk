@extends('layouts.app-2')

@section('page-title', 'Detail Permasalahan - Ruang BK')

@section('body')
<div class="sibk-dashboard" data-page-id="PG-WAKA-CASE-DETAIL">
    <header class="sibk-page-header mb-4">
        <div class="sibk-page-header__copy">
            <a href="{{ route('waka.reports', ['tab' => 'penanganan']) }}" class="text-decoration-none small">&larr; Kembali ke Monitoring Penanganan</a>
            <h1>Detail Permasalahan</h1>
            <p>Informasi layanan yang dapat dibaca Waka Kesiswaan.</p>
        </div>
    </header>

    <div class="alert sibk-read-only-notice d-flex gap-3 align-items-start" role="status">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"/><path d="M12 16v-4M12 8h.01"/></svg>
        <div><strong>Tampilan hanya-baca</strong><p class="mb-0">Perubahan data, catatan internal, dokumen, NISN, dan kode permasalahan tidak tersedia.</p></div>
    </div>

    <section class="sibk-panel" aria-labelledby="waka-case-detail-title">
        <header class="sibk-panel__header">
            <div class="sibk-panel__title-group"><h2 id="waka-case-detail-title">{{ $detail['nama_murid'] }}</h2></div>
            <span class="sibk-badge sibk-badge--{{ $detail['status_code'] === 'selesai' ? 'success' : (in_array($detail['status_code'], ['sedang_diproses', 'membutuhkan_tindak_lanjut'], true) ? 'warning' : 'primary') }}">{{ $detail['status'] }}</span>
        </header>
        <div class="sibk-panel__body p-4">
            <dl class="row g-3 mb-4">
                <div class="col-12 col-md-6"><dt class="text-muted small">Kelas saat layanan</dt><dd class="mb-0 fw-semibold">{{ $detail['kelas'] }}</dd></div>
                <div class="col-12 col-md-6"><dt class="text-muted small">Tanggal pelayanan</dt><dd class="mb-0 fw-semibold">{{ $detail['tanggal'] }}</dd></div>
                <div class="col-12 col-md-6"><dt class="text-muted small">Jenis Masalah</dt><dd class="mb-0 fw-semibold">{{ $detail['bidang'] }}</dd></div>
                <div class="col-12 col-md-6"><dt class="text-muted small">Guru BK penanggung jawab</dt><dd class="mb-0 fw-semibold">{{ $detail['guru_bk'] }}</dd></div>
            </dl>

            <div class="border-top pt-4">
                <h3 class="h6">Informasi awal</h3><p>{{ $detail['initial_info'] }}</p>
                <h3 class="h6">Tindakan awal</h3><p>{{ $detail['initial_action'] }}</p>
                <h3 class="h6">Ringkasan penyelesaian</h3><p>{{ $detail['resolution_summary'] ?: '-' }}</p>
                <h3 class="h6">Jenis tindak lanjut</h3><p class="mb-0">{{ $detail['tindak_lanjut'] }}</p>
            </div>
        </div>
    </section>
</div>
@endsection
