@extends('layouts.app-2')

@section('page-title', 'Atur Penugasan Kelas - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-402">
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

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif

        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <form action="{{ route('assignments.classes.manage') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-12 col-md-8">
                        <label for="filter_tahun_ajaran" class="form-label sibk-form-label">Tahun Ajaran</label>
                        <select class="form-select sibk-form-select" id="filter_tahun_ajaran" name="academic_year_id" required>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" @selected((string) $selectedYear?->id === (string) $year->id)>{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <button type="submit" class="btn btn-outline-primary w-100">Tampilkan Kelas</button>
                    </div>
                </form>
            </div>
        </div>

        @if($selectedYear)
            @php
                [$yearSourceLabel, $yearSourceTone] = match ($selectedYear->master_source) {
                    \App\Models\AcademicYear::MASTER_SOURCE_DAPODIK => ['Terverifikasi Dapodik', 'success'],
                    \App\Models\AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL => ['Sementara', 'warning'],
                    default => ['Data Lama', 'neutral'],
                };
            @endphp
            <section class="sibk-panel mb-4" aria-labelledby="activation-readiness-title">
                <div class="sibk-panel__header p-4 border-0 pb-0">
                    <div>
                        <h2 class="sibk-panel__title mb-1" id="activation-readiness-title">Kesiapan Aktivasi</h2>
                        <p class="sibk-panel__subtitle text-muted small mb-0">
                            Pastikan setiap rombel memiliki tepat satu Guru BK sejak tanggal mulai tahun ajaran.
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="sibk-badge sibk-badge--{{ $yearSourceTone }}">{{ $yearSourceLabel }}</span>
                        <span class="sibk-badge sibk-badge--{{ $selectedYear->is_active ? 'success' : 'info' }}">
                            {{ $selectedYear->is_active ? 'Aktif' : 'Belum Aktif' }}
                        </span>
                    </div>
                </div>
                <div class="sibk-panel__body p-4">
                    @if($activationReadiness['issues'] !== [])
                        <div class="alert alert-warning">
                            <strong>Masih perlu dilengkapi:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($activationReadiness['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table sibk-table mb-0">
                            <thead>
                                <tr>
                                    <th>Rombel</th>
                                    <th>Murid</th>
                                    <th>Guru BK pada Awal Tahun</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activationReadiness['classrooms'] as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['classroom']->name }}</td>
                                        <td>{{ $row['student_count'] }}</td>
                                        <td>{{ $row['teacher_name'] ?? 'Belum lengkap' }}</td>
                                        <td>
                                            <span class="sibk-badge sibk-badge--{{ $row['ready'] ? 'success' : 'warning' }}">
                                                {{ $row['ready'] ? 'Siap' : 'Perlu Penugasan' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Belum ada rombel untuk diperiksa.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($activationReadiness['ready'])
                        <form action="{{ route('assignments.academic-years.activate', $selectedYear) }}" method="POST" class="mt-3 d-flex justify-content-end">
                            @csrf
                            <button type="submit" class="btn btn-primary">Aktifkan Tahun Ajaran</button>
                        </form>
                    @elseif($selectedYear->is_active)
                        <p class="small text-success mb-0 mt-3">Tahun ajaran ini sudah digunakan untuk layanan BK.</p>
                    @else
                        <p class="small text-muted mb-0 mt-3">Tombol aktivasi tersedia setelah semua syarat di atas terpenuhi.</p>
                    @endif
                </div>
            </section>
        @endif

        <form action="{{ route('assignments.classes.store') }}" method="POST">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ old('academic_year_id', $selectedYear?->id) }}">
            <!-- Main Panel: Detail Penugasan -->
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 border-0 pb-0">
                    <div>
                        <h3 class="sibk-panel__title mb-1">Detail Penugasan</h3>
                        <p class="sibk-panel__subtitle text-muted small">Perubahan tidak menghapus riwayat penugasan sebelumnya.</p>
                    </div>
                </div>
                <div class="sibk-panel__body p-4 pt-2">
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label for="kelas" class="form-label sibk-form-label">Kelas <span class="text-danger">*</span></label>
                            <select class="form-select sibk-form-select" id="kelas" name="classroom_id" required>
                                <option value="">Pilih kelas</option>
                                @foreach($classes as $classroom)
                                    <option value="{{ $classroom->id }}" @selected((string) old('classroom_id', $selectedClass?->id) === (string) $classroom->id)>{{ $classroom->name }} — {{ $classroom->academicYear->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="tahun_ajaran_terpilih" class="form-label sibk-form-label">Tahun Ajaran Terpilih</label>
                            <input class="form-control sibk-form-control" id="tahun_ajaran_terpilih" value="{{ $selectedYear?->name ?? 'Tidak tersedia' }}" readonly>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="counselor" class="form-label sibk-form-label">Penanggung Jawab <span class="text-danger">*</span></label>
                            <select class="form-select sibk-form-select" id="counselor" name="user_id" required>
                                <option value="">Pilih Guru BK</option>
                                @foreach($counselors as $guru)
                                    <option value="{{ $guru->id }}" @selected((string) old('user_id') === (string) $guru->id)>{{ $guru->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="start_date" class="form-label sibk-form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control sibk-form-control" id="start_date" name="effective_date" value="{{ old('effective_date', $selectedYear?->starts_on?->toDateString() ?? now()->toDateString()) }}" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="end_date" class="form-label sibk-form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control sibk-form-control" id="end_date" name="effective_until" value="{{ old('effective_until') }}" placeholder="Pilih tanggal bila ada">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="decision_basis" class="form-label sibk-form-label">Dasar Keputusan / Nomor SK</label>
                            <input type="text" class="form-control sibk-form-control" id="decision_basis" name="decision_number" placeholder="Contoh: SK Pembagian Tugas No. 421/089/2026" value="{{ old('decision_number') }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="notes" class="form-label sibk-form-label">Catatan Perubahan</label>
                            <input type="text" class="form-control sibk-form-control" id="notes" name="notes" placeholder="Isi bila pembagian yang sedang berjalan berubah" value="{{ old('notes') }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Box: Ketentuan Tata Kelola (ASN-01 s.d. ASN-06) -->
            <div class="alert alert-info d-flex align-items-start gap-3 p-3 mb-4 rounded-3 border-0 bg-opacity-10 bg-primary">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary flex-shrink-0 mt-1">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <div class="small text-dark">
                    <strong>Ketentuan Tata Kelola Penugasan Guru BK:</strong>
                    <ul class="mb-0 ps-3 mt-1 text-muted">
                        <li>Penetapan penugasan baru akan mengarsipkan penugasan sebelumnya tanpa menghapus rekam jejak histori (<em>ASN-02</em>).</li>
                        <li>Perubahan penugasan kelas di tengah tahun <strong>tidak otomatis memindahkan kasus aktif</strong> yang sedang berjalan (<em>ASN-03</em>). Pengalihan kasus khusus dilakukan terpisah melalui menu <a href="{{ route('assignments.cases.index') }}" class="text-primary fw-semibold text-decoration-none">Pengalihan Kasus</a>.</li>
                    </ul>
                </div>
            </div>

            <!-- Panel: Penugasan Saat Ini -->
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 border-0 pb-0">
                    <div>
                        <h3 class="sibk-panel__title mb-1">Penugasan Saat Ini</h3>
                        <p class="sibk-panel__subtitle text-muted small">Periksa penugasan aktif atau terjadwal sebelum menyimpan perubahan.</p>
                    </div>
                </div>
                <div class="sibk-panel__body p-4 pt-2">
                    @if($currentAssignment)
                    @php
                        $currentAssignmentStatus = $currentAssignment->statusOn(now());
                        [$currentAssignmentLabel, $currentAssignmentTone] = match ($currentAssignmentStatus) {
                            \App\Models\TeacherAssignment::STATUS_ACTIVE => ['Aktif', 'success'],
                            \App\Models\TeacherAssignment::STATUS_SCHEDULED => ['Terjadwal', 'info'],
                            default => ['Berakhir', 'neutral'],
                        };
                    @endphp
                    <div class="sibk-target-highlight-box p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <strong class="text-dark">{{ $currentAssignment->classroom->name }}</strong>
                            <span class="text-muted">•</span>
                            <span class="fw-semibold text-primary">{{ $currentAssignment->teacher->name }}</span>
                            <span class="text-muted">•</span>
                            <span class="text-muted small">Berlaku sejak {{ $currentAssignment->effective_from->locale('id')->translatedFormat('d F Y') }}</span>
                        </div>
                        <span class="sibk-badge sibk-badge--{{ $currentAssignmentTone }}">{{ $currentAssignmentLabel }}</span>
                    </div>
                    @else
                        <div class="text-muted small">Belum ada penugasan aktif atau terjadwal untuk kelas yang dipilih.</div>
                    @endif
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="{{ route('assignments.classes.index') }}" class="btn btn-outline-secondary px-4 py-2">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Simpan Penugasan
                </button>
            </div>
        </form>
    </div>
@endsection
