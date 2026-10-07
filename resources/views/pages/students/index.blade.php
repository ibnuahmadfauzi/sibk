@extends('layouts.app-2')

@section('page-title', 'Daftar Murid - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-201">
        <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4"><div class="sibk-page-header__copy"><h1>Daftar Murid</h1><p>Cari murid dan buka profil layanan sesuai kewenangan Anda.</p></div></div>
        <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><form class="row g-3 align-items-end" action="{{ route('students.index') }}" method="GET" data-auto-filter data-filter-reset-url="{{ route('students.index') }}">
            <div class="col-12 col-md-7"><label class="form-label" for="student_search">Cari murid</label><input class="form-control" id="student_search" name="search" value="{{ request('search') }}" placeholder="Nama atau NISN" data-filter-field></div>
            <div class="col-12 col-md-3"><label class="form-label" for="student_class">Kelas</label><select class="form-select" id="student_class" name="classroom_id" data-filter-field><option value="">Semua kelas</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
            <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100" type="submit" data-filter-action>Filter</button></div>
        </form></div></div>

        <div class="table-responsive"><table class="table sibk-table mb-0 align-middle"><thead><tr><th>No</th><x-sort-header name="murid" label="Murid" /><x-sort-header name="permasalahan" label="Permasalahan" class="text-center" /><x-sort-header name="poin" label="Poin Pelanggaran" class="text-center" /><x-sort-header name="konsultasi" label="Konsultasi" class="text-center" /><x-sort-header name="prestasi" label="Prestasi" class="text-center" /></tr></thead><tbody>
            @forelse($students as $student)
                @php
                    $membership = $student->classMemberships->first();
                    $activeCases = $student->cases->whereNull('closed_at');
                    $totalConsultations = $student->consultations->count();
                    $totalTatibPoints = $student->tatibPoints();
                @endphp
                <tr class="sibk-table__row--clickable" style="cursor:pointer" onclick="window.location.href='{{ route('students.show', $student) }}'">
                    <td>{{ $students->firstItem() + $loop->index }}</td>
                    <td>
                        <a class="d-block fw-bold text-dark text-decoration-none" href="{{ route('students.show', $student) }}">{{ \App\Support\StudentName::display($student->name) }}</a>
                        <div class="small fw-normal text-muted">{{ $student->nisn }} · {{ $membership?->classroom?->name ?? '—' }}</div>
                    </td>
                    <td class="text-center"><span class="fw-semibold {{ $activeCases->isNotEmpty() ? 'text-primary' : 'text-muted' }}">{{ $activeCases->count() }}</span></td>
                    <td class="text-center"><span class="fw-semibold {{ $totalTatibPoints > 0 ? 'text-danger' : 'text-muted' }}">{{ $totalTatibPoints }}</span></td>
                    <td class="text-center"><span class="fw-semibold {{ $totalConsultations > 0 ? 'text-info' : 'text-muted' }}">{{ $totalConsultations }}</span></td>
                    <td class="text-center"><span class="fw-semibold {{ $student->achievements_count > 0 ? 'text-success' : 'text-muted' }}">{{ $student->achievements_count }}</span></td>
                </tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data murid yang sesuai.</td></tr>@endforelse
        </tbody></table></div>@if($students->hasPages())<div class="mt-3"><div class="text-muted small mb-2">Menampilkan {{ $students->count() }} dari {{ $students->total() }} murid</div>{{ $students->links() }}</div>@endif
    </div>
@endsection
