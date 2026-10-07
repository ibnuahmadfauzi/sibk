@extends('layouts.app-2')

@section('page-title', 'Data Kelas - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <h1>Data Kelas</h1>
                <p>Rombel aktif tersedia otomatis saat tahun ajaran baru dibuat.</p>
            </div>
        </div>

        @if(session('success'))
            <x-notification-toast>{{ session('success') }}</x-notification-toast>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Periksa kembali data kelas.</strong>
                <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if($jurusan === null)
            <section class="sibk-panel mb-4" aria-label="Tambah Rombel">
                <div class="sibk-panel__body p-3">
                    @include('pages.data-master._classroom-create-form')
                </div>
            </section>
        @else
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('data-master.classrooms.index') }}" class="btn btn-icon btn-light text-primary flex-shrink-0" aria-label="Kembali ke semua jurusan" title="Kembali ke semua jurusan">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg>
                    </a>
                    <h2 class="fs-5 mb-0">Rombel Jurusan {{ $jurusan }} @if(isset($majorNames[$jurusan]))({{ $majorNames[$jurusan] }})@endif</h2>
                </div>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createClassroomModal">Tambah Rombel</button>
            </div>
            <div class="modal fade" id="createClassroomModal" tabindex="-1" aria-labelledby="createClassroomTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="createClassroomTitle">Tambah Rombel</h2>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">@include('pages.data-master._classroom-create-form')</div>
                </div></div>
            </div>
        @endif

        <section class="sibk-panel" aria-label="{{ $jurusan === null ? 'Jurusan' : 'Rombel Jurusan '.$jurusan }}">
            <div class="table-responsive">
                <table class="table sibk-table sibk-classroom-table mb-0 align-middle" data-client-sort>
                    @if($jurusan === null)
                    <thead><tr><x-client-sort-header label="Jurusan" /><x-client-sort-header label="Rombel" type="number" /><x-client-sort-header label="Murid" type="number" /><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($groups as $name => $group)
                            <tr>
                                <td class="fw-semibold">{{ $name }} @if(isset($majorNames[$name]))({{ $majorNames[$name] }})@endif</td>
                                <td>{{ $group->count() }}</td>
                                <td>{{ $group->sum(fn ($classroom) => $studentCounts->get($classroom->id, 0)) }}</td>
                                <td><a class="btn btn-sm p-0 sibk-icon-button sibk-report-control" href="{{ route('data-master.classrooms.index', ['jurusan' => $name]) }}" aria-label="Lihat Rombel" title="Lihat Rombel">
                                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></svg>
                                </a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada rombel. Tambahkan rombel sekolah untuk memulai.</td></tr>
                        @endforelse
                    </tbody>
                    @else
                    <thead><tr><x-client-sort-header label="Nama Rombel" /><x-client-sort-header label="Murid" type="number" /><th scope="col">Status</th></tr></thead>
                    <tbody>
                        @foreach($groups->get($jurusan) as $classroom)
                            <tr>
                                <td>
                                    <button class="btn btn-link fw-semibold p-0 text-start sibk-classroom-name" type="button"
                                        data-bs-toggle="modal" data-bs-target="#renameClassroomModal"
                                        data-classroom-name="{{ $classroom->name }}"
                                        data-classroom-active="{{ $classroom->is_active ? '1' : '0' }}"
                                        data-classroom-update-url="{{ route('data-master.classrooms.update', $classroom) }}"
                                        aria-label="Edit rombel {{ $classroom->name }}">{{ $classroom->name }}</button>
                                </td>
                                <td>{{ $studentCounts->get($classroom->id, 0) }}</td>
                                <td>
                                    <span class="sibk-badge sibk-badge--{{ $classroom->is_active ? 'success' : 'danger' }}">{{ $classroom->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    @endif
                </table>
            </div>
        </section>
    </div>

    <div class="modal fade" id="renameClassroomModal" tabindex="-1" aria-labelledby="renameClassroomTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" data-classroom-rename-form>
                    @csrf @method('PATCH')

                    @if($jurusan !== null)<input type="hidden" name="jurusan" value="{{ $jurusan }}">@endif
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="renameClassroomTitle">Edit Rombel</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="rename-classroom-name">Nama Rombel</label>
                        <input class="form-control" id="rename-classroom-name" name="name" maxlength="100" required>
                        <label class="form-label mt-3" for="classroom-status">Status</label>
                        <select class="form-select" id="classroom-status" name="is_active">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                        <p class="small text-muted mb-0 mt-2">Nama di tahun ajaran yang sudah selesai tetap tersimpan.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
