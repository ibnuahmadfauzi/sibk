@extends('layouts.app-2')

@section('page-title', 'Daftar Prestasi - Ruang BK')

@section('body')
<div class="sibk-dashboard">
    <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div class="sibk-page-header__copy"><h1>Daftar Prestasi</h1><p>{{ $canCreateAchievement ? 'Kelola prestasi murid melalui pencatatan atau impor Excel.' : 'Lihat prestasi murid sesuai kewenangan Anda.' }}</p></div>
        @if($canCreateAchievement)
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#achievement-import-modal">Impor Excel</button>
                <a href="{{ route('achievements.create') }}" data-modal-url="{{ route('achievements.create', ['modal' => 1, 'student_id' => request('student_id')]) }}" @if(request()->boolean('create')) data-modal-auto-open @endif class="btn btn-primary">Catat Prestasi</a>
            </div>
        @endif
    </div>
    @if(session('success'))
        <x-notification-toast>{{ session('success') }}</x-notification-toast>
    @endif
    @if($errors->any() && ! $errors->has('file'))
        <x-notification-toast tone="error" title="Perubahan belum tersimpan">{{ $errors->first() }}</x-notification-toast>
    @endif
    <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4">
        <form action="{{ route('achievements.index') }}" method="GET" class="row g-3 align-items-end" data-auto-filter data-filter-reset-url="{{ route('achievements.index') }}">
            <div class="col-12 col-lg-6"><label for="achievement_search" class="form-label">Cari</label><input id="achievement_search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Murid, NISN, kegiatan, atau penyelenggara" data-filter-field></div>
            <div class="col-12 col-md-6 col-lg-3"><label for="achievement_level" class="form-label">Tingkat Prestasi</label><select id="achievement_level" name="level_id" class="form-select" data-filter-field><option value="">Semua tingkat</option>@foreach($levels as $level)<option value="{{ $level->id }}" @selected((string) request('level_id') === (string) $level->id)>{{ $level->label }}</option>@endforeach</select></div>
            <div class="col-12 col-md-6 col-lg-3"><button class="btn btn-outline-primary w-100" type="submit" data-filter-action>Filter</button></div>
        </form>
    </div></div>
    <div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th scope="col">Murid</th><th scope="col">Kegiatan</th><th scope="col">Jenis / Tingkat</th><th scope="col">Tanggal</th><th scope="col">Hasil</th><th scope="col">Aksi</th></tr></thead><tbody>
        @forelse($achievements as $achievement)
            <tr>
                <td><strong>{{ \App\Support\StudentName::display($achievement->student->name) }}</strong><span class="small text-muted d-block">{{ $achievement->student->nisn }}</span></td>
                <td>{{ $achievement->activity_name }}<span class="small text-muted d-block">{{ $achievement->organizer }}</span></td>
                <td>{{ $achievement->type->label }}<span class="small text-muted d-block">{{ $achievement->level->label }}</span></td>
                <td class="text-nowrap">{{ $achievement->achievement_date->locale('id')->translatedFormat('d M Y') }}</td>
                <td>{{ $achievement->result }}</td>
                <td><div class="d-flex gap-2">
                    @can('update', $achievement)<a href="{{ route('achievements.edit', $achievement) }}" data-modal-url="{{ route('achievements.edit', [$achievement, 'modal' => 1]) }}" class="btn btn-sm btn-link d-inline-flex p-2" title="Edit prestasi" aria-label="Edit prestasi">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931z"/></svg>
                    </a>@endcan
                    @can('delete', $achievement)
                        <form action="{{ route('achievements.destroy', $achievement) }}" method="POST"
                            data-app-confirm-submit data-confirm-tone="danger" data-confirm-title="Hapus prestasi?"
                            data-confirm-message="Hapus prestasi" data-confirm-subject="{{ $achievement->activity_name }}"
                            data-confirm-suffix=" untuk {{ \App\Support\StudentName::display($achievement->student->name) }}?" data-confirm-action="Ya, hapus">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="expected_updated_at" value="{{ $achievement->updated_at?->toJSON() }}">
                            <button type="submit" class="btn btn-sm btn-link text-danger d-inline-flex p-2" title="Hapus prestasi" aria-label="Hapus prestasi">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"/></svg>
                            </button>
                        </form>
                    @endcan
                </div></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">Belum ada prestasi yang sesuai dengan filter dan kewenangan Anda.</td></tr>
        @endforelse
    </tbody></table></div>
    @if($achievements->hasPages())<div class="mt-3">{{ $achievements->links() }}</div>@endif
    @if($canCreateAchievement)
        @include('pages.achievements._import-modal')
        @if(request()->integer('edit') > 0)
            <button type="button" class="d-none" data-modal-url="{{ route('achievements.edit', ['achievement' => request()->integer('edit'), 'modal' => 1]) }}" data-modal-auto-open>Edit prestasi</button>
        @endif
    @endif
    <div class="modal fade" id="achievement-modal" tabindex="-1" aria-labelledby="achievement-modal-title" aria-hidden="true" data-service-record-modal>
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div data-modal-submit-error>
                <x-notification-toast tone="error" title="Gagal menyimpan"><span data-modal-submit-error-message></span></x-notification-toast>
            </div>
            <div class="modal-content">
            <div class="modal-header"><h2 class="modal-title fs-5" id="achievement-modal-title">Prestasi</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><p class="text-muted mb-0">Memuat data&hellip;</p></div>
        </div></div>
    </div>
</div>
@endsection

@section('extra-javascript')
@if($canCreateAchievement && $errors->has('file'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('achievement-import-modal')).show();
    });
</script>
@endif
@endsection
