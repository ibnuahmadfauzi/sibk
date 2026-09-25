@php
    $sourcePresentation = static fn (string $source): array => match ($source) {
        \App\Models\AcademicYear::MASTER_SOURCE_DAPODIK => ['Terverifikasi Dapodik', 'success'],
        \App\Models\AcademicYear::MASTER_SOURCE_SCHOOL_PROVISIONAL => ['Sementara', 'warning'],
        default => ['Data Lama', 'neutral'],
    };
@endphp

<section class="sibk-panel mb-4" aria-labelledby="academic-year-preparation-title">
    <div class="sibk-panel__header p-4 border-0 pb-0">
        <div>
            <h2 class="sibk-panel__title mb-1" id="academic-year-preparation-title">Persiapan Tahun Ajaran</h2>
            <p class="sibk-panel__subtitle text-muted small mb-0">
                Gunakan langkah ini bila data tahun ajaran baru dari Dapodik belum tersedia.
            </p>
        </div>
    </div>
    <div class="sibk-panel__body p-4">
        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <h3 class="fs-6 fw-bold mb-3">1. Buat Tahun Ajaran Sementara</h3>
                <form action="{{ route('data-master.academic-years.store') }}" method="POST" class="row g-3" data-autosave-form="academic-year-preparation">
                    @csrf
                    <div class="col-12 col-sm-6">
                        <label for="provisional_year_name" class="form-label sibk-form-label">Tahun Ajaran</label>
                        <input
                            class="form-control sibk-form-control @error('name') is-invalid @enderror"
                            id="provisional_year_name"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="2027/2028"
                            required
                        >
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="provisional_year_reference" class="form-label sibk-form-label">Dasar Resmi Sekolah</label>
                        <input
                            class="form-control sibk-form-control @error('preparation_reference') is-invalid @enderror"
                            id="provisional_year_reference"
                            name="preparation_reference"
                            value="{{ old('preparation_reference') }}"
                            placeholder="Nomor SK atau kalender pendidikan"
                            required
                        >
                        @error('preparation_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex align-items-center gap-2">
                        <span class="small text-muted me-auto" data-draft-status aria-live="polite"></span>
                        <button type="button" class="btn btn-light" data-clear-draft>Hapus Draft</button>
                        <button type="submit" class="btn btn-primary">Buat Tahun Ajaran</button>
                    </div>
                </form>
            </div>

            <div class="col-12 col-xl-7">
                <h3 class="fs-6 fw-bold mb-3">2. Impor Daftar Murid</h3>
                <p class="text-muted small">
                    CSV UTF-8 maksimal 2 MiB, maksimal 5.000 baris, dengan header tepat
                    <code>nisn,nama,rombel</code>.
                </p>
                @error('academic_year')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                @error('file')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

                <div class="d-flex flex-column gap-3">
                    @forelse($preparationYears as $year)
                        @php([$sourceLabel, $sourceTone] = $sourcePresentation($year->master_source))
                        <div class="border rounded-3 p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <strong>{{ $year->name }}</strong>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="sibk-badge sibk-badge--{{ $sourceTone }}">{{ $sourceLabel }}</span>
                                    <span class="sibk-badge sibk-badge--{{ $year->is_active ? 'success' : 'info' }}">
                                        {{ $year->is_active ? 'Aktif' : 'Belum Aktif' }}
                                    </span>
                                </div>
                            </div>
                            <div class="small mb-3">
                                <span class="me-3">{{ $year->active_classroom_count }} rombel</span>
                                <span>{{ $year->active_student_count }} keanggotaan murid</span>
                                @if($year->preparation_reference)
                                    <span class="text-muted d-block mt-1">Dasar: {{ $year->preparation_reference }}</span>
                                @endif
                            </div>
                            @if(! $year->is_active
                                && $year->activated_at === null
                                && ! $year->classrooms_exists
                                && ! $year->student_class_memberships_exists
                                && ! $year->teacher_assignments_exists)
                                <button
                                    class="btn btn-outline-danger btn-sm mb-3"
                                    type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#delete-year-{{ $year->id }}"
                                >
                                    Hapus draf kosong
                                </button>
                                <div
                                    class="modal fade"
                                    id="delete-year-{{ $year->id }}"
                                    tabindex="-1"
                                    aria-labelledby="delete-year-title-{{ $year->id }}"
                                    aria-hidden="true"
                                >
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h2 class="modal-title fs-5" id="delete-year-title-{{ $year->id }}">
                                                    Hapus draf {{ $year->name }}?
                                                </h2>
                                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                            </div>
                                            <div class="modal-body">
                                                Tahun Persiapan kosong ini akan dihapus. Tindakan ini tidak dapat dibatalkan.
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Kembali</button>
                                                <form
                                                    action="{{ route('data-master.academic-years.destroy', $year) }}"
                                                    method="POST"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-danger" type="submit">Hapus draf</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(! $year->is_active)
                                <form
                                    action="{{ route('data-master.academic-years.roster-imports.store', $year) }}"
                                    method="POST"
                                    enctype="multipart/form-data"
                                    class="row g-2 align-items-end"
                                >
                                    @csrf
                                    <div class="col-12 col-md">
                                        <label class="form-label small" for="roster_file_{{ $year->id }}">Pilih berkas CSV</label>
                                        <input class="form-control" type="file" accept=".csv,text/csv" id="roster_file_{{ $year->id }}" name="file" required>
                                    </div>
                                    <div class="col-12 col-md-auto">
                                        <button type="submit" class="btn btn-outline-primary w-100">Impor Daftar</button>
                                    </div>
                                </form>
                            @else
                                <p class="small text-success mb-0">Tahun ajaran ini sudah aktif. Daftar sementara tidak dapat diimpor lagi.</p>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted small border rounded-3 p-3">
                            Belum ada tahun ajaran. Buat tahun ajaran sementara terlebih dahulu.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-4 mb-0">
            Setelah daftar masuk, Koordinator BK melengkapi penugasan setiap rombel dan mengaktifkan tahun ajaran.
            Status <strong>Sementara</strong> tetap tampil sampai data cocok dengan Dapodik.
        </div>
    </div>
</section>
