@extends('layouts.app-2')

@section('page-title', 'Atur Penugasan Kelas - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Page Header -->
        <div class="sibk-page-header mb-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <a href="{{ route('assignments.classes.index') }}" class="sibk-back-link d-inline-flex align-items-center gap-1 text-decoration-none text-muted small fw-semibold">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Penugasan Kelas
                </a>
            </div>
            <div class="sibk-page-header__copy">
                <h1>Atur Penugasan Kelas</h1>
                <p>Tetapkan penanggung jawab untuk kelas dan periode tertentu.</p>
            </div>
        </div>

        <form action="{{ route('assignments.classes.index') }}" method="GET">
            <div class="row g-4">
                <!-- Left Panel: Form Detail Penugasan -->
                <div class="col-12 col-lg-8">
                    <div class="sibk-panel">
                        <div class="sibk-panel__header p-4 border-0 pb-0">
                            <div>
                                <h3 class="sibk-panel__title mb-1">Detail Penugasan</h3>
                                <p class="sibk-panel__subtitle text-muted small">Perubahan tidak menghapus riwayat penugasan sebelumnya.</p>
                            </div>
                        </div>
                        <div class="sibk-panel__body p-4 pt-2">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="kelas" class="form-label sibk-form-label">Kelas <span class="text-danger">*</span></label>
                                    <select class="form-select sibk-form-select" id="kelas" name="kelas" required>
                                        <option value="">Pilih kelas</option>
                                        @foreach($classes as $c)
                                            <option value="{{ $c }}" {{ request()->query('class') === $c || $c === 'X RPL 1' ? 'selected' : '' }}>{{ $c }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="tahun_ajaran" class="form-label sibk-form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control sibk-form-control bg-light" id="tahun_ajaran" name="tahun_ajaran" value="2026/2027" readonly>
                                </div>
                                <div class="col-12">
                                    <label for="counselor" class="form-label sibk-form-label">Penanggung Jawab <span class="text-danger">*</span></label>
                                    <select class="form-select sibk-form-select" id="counselor" name="counselor" required>
                                        <option value="">Pilih Guru BK</option>
                                        @foreach($counselors as $guru)
                                            <option value="{{ $guru }}" {{ $guru === 'Guru BK A' ? 'selected' : '' }}>{{ $guru }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="start_date" class="form-label sibk-form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control sibk-form-control" id="start_date" name="start_date" value="2026-07-15" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="end_date" class="form-label sibk-form-label">Tanggal Akhir</label>
                                    <input type="date" class="form-control sibk-form-control" id="end_date" name="end_date" placeholder="Pilih tanggal bila ada">
                                </div>
                                <div class="col-12">
                                    <label for="notes" class="form-label sibk-form-label">Catatan Perubahan</label>
                                    <textarea class="form-control sibk-form-control" id="notes" name="notes" rows="3" placeholder="Isi bila pembagian yang sedang berjalan berubah"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel: Penugasan Saat Ini Info Box -->
                <div class="col-12 col-lg-4">
                    <div class="sibk-panel">
                        <div class="sibk-panel__header p-4 border-0 pb-0">
                            <div>
                                <h3 class="sibk-panel__title mb-1">Penugasan Saat Ini</h3>
                                <p class="sibk-panel__subtitle text-muted small">Periksa kondisi aktif sebelum menyimpan perubahan.</p>
                            </div>
                        </div>
                        <div class="sibk-panel__body p-4 pt-2">
                            <div class="sibk-assignment-info-card p-3 rounded border mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong class="text-dark">{{ $currentAssignment['class'] }}</strong>
                                    <span class="sibk-badge sibk-badge--success">{{ $currentAssignment['status'] }}</span>
                                </div>
                                <div class="text-muted small mb-1">Penanggung Jawab: <span class="fw-semibold text-dark">{{ $currentAssignment['counselor'] }}</span></div>
                                <div class="text-muted small">Berlaku sejak {{ $currentAssignment['start_date'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="{{ route('assignments.classes.index') }}" class="btn btn-outline-secondary px-4 py-2">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary px-4 py-2">
                    Simpan Penugasan
                </button>
            </div>
        </form>
    </div>
@endsection
