@extends('layouts.app-2')

@section('page-title', 'Daftar Prestasi - Ruang BK')

@section('body')
<div class="sibk-dashboard">
    <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div class="sibk-page-header__copy"><h1>Daftar Prestasi</h1><p>{{ $canCreateAchievement ? 'Kelola prestasi murid melalui pencatatan atau impor Excel.' : 'Lihat prestasi murid sesuai kewenangan Anda.' }}</p></div>
        @if($canCreateAchievement)<a href="{{ route('achievements.create') }}" class="btn btn-primary">Catat Prestasi</a>@endif
    </div>
    @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Periksa berkas dan datanya:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if($canCreateAchievement)
        <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4">
            <details class="mb-3">
                <summary class="d-block" aria-label="Lihat syarat file Excel" title="Lihat syarat file Excel">
                    <h2 class="fs-5 mb-0 d-flex align-items-center gap-1">Impor Prestasi dari Excel
                        <span class="btn btn-link d-inline-flex p-2" aria-hidden="true"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v6m0-10v.01"/></svg></span>
                    </h2>
                </summary>
                <div class="alert alert-info mt-3 mb-0">
                    <p class="mb-2">Gunakan berkas .xlsx dengan kolom berurutan: <strong>nisn, jenis, tingkat, kegiatan, penyelenggara, tanggal, hasil</strong>. Tanggal diisi YYYY-MM-DD. Maksimal 1.000 baris dan 2 MB. Seluruh baris diperiksa sebelum disimpan.</p>
                    <p class="mb-0">Kode jenis: {{ $types->pluck('code')->join(', ') }}. Kode tingkat: {{ $levels->pluck('code')->join(', ') }}.</p>
                </div>
            </details>
            <form action="{{ route('achievements.import') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                @csrf
                <div class="col-12 col-md-8"><label for="achievement_import_file" class="form-label">Berkas Excel</label><input type="file" id="achievement_import_file" name="file" class="form-control" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div>
                <div class="col-12 col-md-4"><button type="submit" class="btn btn-primary w-100">Impor Prestasi</button></div>
            </form>
        </div></div>
    @endif
    <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4">
        @php($hasActiveFilters = trim((string) request('search')) !== '' || request()->filled('classroom_id'))
        <form action="{{ route('achievements.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-lg-6"><label for="achievement_search" class="form-label">Cari</label><input id="achievement_search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Murid, NISN, kegiatan, atau penyelenggara"></div>
            <div class="col-12 col-md-6 col-lg-3"><label for="achievement_class" class="form-label">Kelas</label><select id="achievement_class" name="classroom_id" class="form-select"><option value="">Semua kelas</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
            <div class="col-12 col-md-6 col-lg-3">@if($hasActiveFilters)<a href="{{ route('achievements.index') }}" class="btn btn-light w-100">Reset</a>@else<button class="btn btn-primary w-100">Tampilkan</button>@endif</div>
        </form>
    </div></div>
    <div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>Murid</th><th>Kegiatan</th><th>Jenis / Tingkat</th><th>Tanggal</th><th>Hasil</th><th>Aksi</th></tr></thead><tbody>
        @forelse($achievements as $achievement)
            <tr>
                <td><strong>{{ $achievement->student->name }}</strong><span class="small text-muted d-block">{{ $achievement->student->nisn }}</span></td>
                <td>{{ $achievement->activity_name }}<span class="small text-muted d-block">{{ $achievement->organizer }}</span></td>
                <td>{{ $achievement->type->label }}<span class="small text-muted d-block">{{ $achievement->level->label }}</span></td>
                <td>{{ $achievement->achievement_date->locale('id')->translatedFormat('d M Y') }}</td>
                <td>{{ $achievement->result }}</td>
                <td><div class="d-flex gap-2">
                    <a href="{{ route('achievements.show', $achievement) }}" data-modal-url="{{ route('achievements.show', [$achievement, 'modal' => 1]) }}" class="btn btn-sm btn-outline-info d-inline-flex p-2" title="Lihat selengkapnya" aria-label="Lihat selengkapnya">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v6m0-10v.01"/></svg>
                    </a>
                    @can('update', $achievement)<a href="{{ route('achievements.edit', $achievement) }}" data-modal-url="{{ route('achievements.edit', [$achievement, 'modal' => 1]) }}" class="btn btn-sm btn-link d-inline-flex p-2" title="Edit prestasi" aria-label="Edit prestasi">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931z"/></svg>
                    </a>@endcan
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada prestasi yang sesuai dengan filter dan kewenangan Anda.</td></tr>
        @endforelse
    </tbody></table></div>
    @if($achievements->hasPages())<div class="mt-3">{{ $achievements->links() }}</div>@endif
    <div class="modal fade" id="achievement-modal" tabindex="-1" aria-labelledby="achievement-modal-title" aria-hidden="true" data-service-record-modal>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h2 class="modal-title fs-5" id="achievement-modal-title">Detail Prestasi</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><p class="text-muted mb-0">Memuat data&hellip;</p></div>
        </div></div>
    </div>
</div>
@endsection
