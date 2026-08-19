@extends('layouts.app-2')

@section('page-title', 'Penugasan & Pengalihan Kasus - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Page Header -->
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Penugasan dan Pengalihan Kasus</h1>
                <p>Atur penanggung jawab atau kewenangan tambahan untuk kasus tertentu.</p>
            </div>
        </div>

        <form action="{{ route('cases.index') }}" method="GET">
            <div class="row g-4">
                <!-- Left Column: Kasus Target & Detail Perubahan -->
                <div class="col-12 col-lg-8">
                    <!-- Kasus Target Panel -->
                    <div class="sibk-panel mb-4">
                        <div class="sibk-panel__header p-4 border-0 pb-0">
                            <h3 class="sibk-panel__title">Kasus Target</h3>
                        </div>
                        <div class="sibk-panel__body p-4 pt-2">
                            <div class="mb-3">
                                <label for="kasus" class="form-label sibk-form-label">Kasus <span class="text-danger">*</span></label>
                                <select class="form-select sibk-form-select" id="kasus" name="kasus" required>
                                    @foreach($cases as $c)
                                        <option value="{{ $c['no'] }}" {{ $c['no'] === 'K-014' ? 'selected' : '' }}>
                                            {{ $c['no'] }} — {{ $c['student'] }} ({{ $c['class'] }}) • {{ $c['status'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sibk-target-highlight-box p-3 rounded d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-primary">{{ $selectedCase['no'] }}</span>
                                    <span class="text-muted">•</span>
                                    <span class="fw-semibold text-dark">{{ $selectedCase['student'] }}</span>
                                    <span class="text-muted">•</span>
                                    <span>{{ $selectedCase['class'] }}</span>
                                </div>
                                <span class="sibk-badge sibk-badge--primary">{{ $selectedCase['status'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Perubahan Panel -->
                    <div class="sibk-panel">
                        <div class="sibk-panel__header p-4 border-0 pb-0">
                            <h3 class="sibk-panel__title">Detail Perubahan</h3>
                        </div>
                        <div class="sibk-panel__body p-4 pt-2">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="change_type" class="form-label sibk-form-label">Jenis Perubahan <span class="text-danger">*</span></label>
                                    <select class="form-select sibk-form-select" id="change_type" name="change_type" required>
                                        <option value="">Pilih jenis perubahan</option>
                                        <option value="pengalihan" selected>Pengalihan Penanggung Jawab</option>
                                        <option value="tambahan">Penambahan Akses / Tim Pendamping</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="counselor_recipient" class="form-label sibk-form-label">Penerima Penugasan <span class="text-danger">*</span></label>
                                    <select class="form-select sibk-form-select" id="counselor_recipient" name="counselor_recipient" required>
                                        <option value="">Pilih Guru BK</option>
                                        @foreach($counselors as $guru)
                                            <option value="{{ $guru }}" {{ $guru === 'Guru BK B' ? 'selected' : '' }}>{{ $guru }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="effective_date" class="form-label sibk-form-label">Tanggal Berlaku <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control sibk-form-control" id="effective_date" name="effective_date" value="2026-08-19" required>
                                </div>
                                <div class="col-12">
                                    <label for="reason" class="form-label sibk-form-label">Alasan <span class="text-danger">*</span></label>
                                    <textarea class="form-control sibk-form-control" id="reason" name="reason" rows="3" placeholder="Tuliskan alasan perubahan" required>Kebutuhan pendampingan khusus dan penyesuaian beban kerja.</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Ringkasan Perubahan -->
                <div class="col-12 col-lg-4">
                    <div class="sibk-panel">
                        <div class="sibk-panel__header p-4 border-0 pb-0">
                            <h3 class="sibk-panel__title mb-1">Ringkasan Perubahan</h3>
                            <p class="sibk-panel__subtitle text-muted small">Periksa kasus, penerima, jenis perubahan, dan tanggal berlaku sebelum menyimpan.</p>
                        </div>
                        <div class="sibk-panel__body p-4 pt-2">
                            <div class="sibk-account-info-box">
                                <h4 class="sibk-account-info-box__title">Pemberitahuan Otomatis</h4>
                                <p class="sibk-account-info-box__desc mb-0">
                                    Setelah penugasan atau pengalihan disimpan, Guru BK penerima akan menerima notifikasi dan riwayat perubahan akan dicatat pada audit log.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="{{ route('cases.index') }}" class="btn btn-outline-secondary px-4 py-2">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary px-4 py-2">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
