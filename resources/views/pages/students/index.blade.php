@extends('layouts.app-2')

@section('page-title', 'Daftar Murid - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-201">
        <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4"><div class="sibk-page-header__copy"><h1>Daftar Murid</h1><p>Cari murid dan buka profil layanan sesuai kewenangan Anda.</p></div><a href="{{ route('achievements.index') }}" class="btn btn-outline-primary">Kelola Prestasi</a></div>
        <div class="sibk-panel mb-4"><div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Pencarian Murid</h2></div><div class="sibk-panel__body p-4"><form class="row g-3 align-items-end" action="{{ route('students.index') }}" method="GET">
            <div class="col-12 col-md-7"><label class="form-label" for="student_search">Cari murid</label><input class="form-control" id="student_search" name="search" value="{{ request('search') }}" placeholder="Nama atau NISN"></div>
            <div class="col-12 col-md-3"><label class="form-label" for="student_class">Kelas</label><select class="form-select" id="student_class" name="classroom_id"><option value="">Semua kelas</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
            <div class="col-12 col-md-2"><button class="btn btn-primary w-100">Cari</button></div>
        </form></div></div>

        <div class="sibk-panel"><div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>NISN</th><th>Nama Murid</th><th>Kelas Aktif</th><th>Kasus Aktif</th><th>Tindak Lanjut</th><th></th></tr></thead><tbody>
            @forelse($students as $student)
                @php
                    $membership = $student->classMemberships->first();
                    $activeCases = $student->cases->whereNull('closed_at');
                    $nextFollowUp = $student->cases->flatMap->followUps->filter(fn ($item) => $item->planned_date->gte(today()) && $item->status?->code !== 'dibatalkan')->sortBy('planned_date')->first();
                @endphp
                <tr><td class="fw-semibold">{{ $student->nisn }}</td><td class="fw-semibold">{{ $student->name }}</td><td>{{ $membership?->classroom?->name ?? '—' }}</td><td><span class="fw-semibold {{ $activeCases->isNotEmpty() ? 'text-primary' : 'text-muted' }}">{{ $activeCases->count() }}</span></td><td>{{ $nextFollowUp?->planned_date?->locale('id')->translatedFormat('d M Y') ?? 'Belum ada' }}</td><td><a href="{{ route('students.show', $student) }}" class="fw-bold text-decoration-none">Buka</a></td></tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data murid yang sesuai.</td></tr>@endforelse
        </tbody></table></div><div class="p-4 border-top"><div class="text-muted small mb-2">Menampilkan {{ $students->count() }} dari {{ $students->total() }} murid</div>{{ $students->links() }}</div></div>
    </div>
@endsection
