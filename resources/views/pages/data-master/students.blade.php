@extends('layouts.app-2')

@section('page-title', 'Master Murid - Ruang BK')

@section('body')
    @php
        $sourcePresentation = static fn (?string $source): array => match ($source) {
            \App\Models\Student::MASTER_SOURCE_SCHOOL_PROVISIONAL => ['Sementara', 'warning'],
            \App\Models\Student::MASTER_SOURCE_DAPODIK => ['Terverifikasi Sumber Resmi', 'success'],
            default => ['Data Lama', 'neutral'],
        };
    @endphp

    <div class="sibk-dashboard" data-page-id="PG-501-STUDENTS">
        <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy">
                <a
                    href="{{ route('data-master.index') }}"
                    class="text-decoration-none small"
                >
                    &larr; Data Master
                </a>
                <h1>Master Murid</h1>
                <p>Periksa identitas dan penempatan kelas hasil impor tanpa membuka data layanan BK.</p>
            </div>
        </div>

        <section class="sibk-panel mb-4" aria-labelledby="student-master-filter-title">
            <div class="sibk-panel__body p-4">
                <h2 class="visually-hidden" id="student-master-filter-title">Filter master murid</h2>
                <form
                    class="row g-3 align-items-end"
                    action="{{ route('data-master.students.index') }}"
                    method="GET"
                >
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="master_student_search">Cari murid</label>
                        <input
                            class="form-control"
                            id="master_student_search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Nama atau NISN"
                        >
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="master_academic_year">Tahun Ajaran</label>
                        <select
                            class="form-select"
                            id="master_academic_year"
                            name="academic_year_id"
                        >
                            <option value="">Semua tahun ajaran</option>
                            @foreach($academicYears as $academicYear)
                                <option
                                    value="{{ $academicYear->id }}"
                                    @selected($selectedAcademicYearId === $academicYear->id)
                                >
                                    {{ $academicYear->name }}{{ $academicYear->is_active ? ' - Aktif' : ' - Belum Aktif' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit">Terapkan</button>
                    </div>
                </form>
            </div>
        </section>

        <div class="table-responsive">
            <table class="table sibk-table mb-0">
                <thead>
                    <tr>
                        <th>NISN</th>
                        <th>Nama Murid</th>
                        <th>Rombel</th>
                        <th>Tahun Ajaran</th>
                        <th>Status Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php($membership = $student->classMemberships->first())
                        @php([$sourceLabel, $sourceTone] = $sourcePresentation($membership?->master_source ?? $student->master_source))
                        <tr>
                            <td class="fw-semibold">{{ $student->nisn }}</td>
                            <td class="fw-semibold">{{ $student->name }}</td>
                            <td>{{ $membership?->classroom?->name ?? '-' }}</td>
                            <td>
                                {{ $membership?->academicYear?->name ?? '-' }}
                                @if($membership?->academicYear !== null)
                                    <span class="d-block small text-muted">
                                        {{ $membership->academicYear->is_active ? 'Aktif' : 'Belum Aktif' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="sibk-badge sibk-badge--{{ $sourceTone }}">
                                    {{ $sourceLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Tidak ada data murid yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="mt-3">
                <div class="text-muted small mb-2">
                    Menampilkan {{ $students->count() }} dari {{ $students->total() }} murid
                </div>
                {{ $students->links() }}
            </div>
        @endif
    </div>
@endsection
