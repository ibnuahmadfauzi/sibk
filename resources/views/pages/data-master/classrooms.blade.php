@extends('layouts.app-2')

@section('page-title', 'Data Kelas - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <h1>Data Kelas</h1>
                <p>Rombel aktif otomatis tersedia saat tahun ajaran baru dibuat.</p>
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

        <section class="sibk-panel mb-4" aria-labelledby="add-classroom-title">
            <div class="sibk-panel__body p-4">
                <h2 class="fs-5 mb-2" id="add-classroom-title">Tambah rombel</h2>
                <p class="text-muted mb-3">Rombel baru langsung tersedia pada tahun ajaran Persiapan dan aktif.</p>
                <form class="row g-3 align-items-end" action="{{ route('data-master.classrooms.store') }}" method="POST">
                    @csrf
                    <div class="col-12 col-md-8">
                        <label class="form-label" for="classroom-name">Nama rombel</label>
                        <input class="form-control" id="classroom-name" name="name" value="{{ old('name') }}" maxlength="100" placeholder="Contoh: X RPL 1" required>
                    </div>
                    <div class="col-12 col-md-4"><button class="btn btn-primary w-100" type="submit">Tambah Rombel</button></div>
                </form>
            </div>
        </section>

        <section class="sibk-panel" aria-labelledby="classroom-list-title">
            <div class="sibk-panel__header">
                <div>
                    <h2 class="sibk-panel__title" id="classroom-list-title">{{ $jurusan === null ? 'Daftar Jurusan' : 'Rombel Jurusan '.$jurusan }}</h2>
                    <p class="sibk-panel__subtitle">{{ $jurusan === null ? 'Lihat daftar rombel pada setiap jurusan.' : 'Klik nama untuk menggantinya. Gunakan tombol status untuk mengaktifkan atau menonaktifkan rombel.' }}</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($jurusan !== null)<a class="btn btn-outline-secondary btn-sm" href="{{ route('data-master.classrooms.index') }}">Semua Jurusan</a>@endif
                    <span class="sibk-badge sibk-badge--primary">{{ $jurusan === null ? $classrooms->count() : $groups->get($jurusan)->count() }} rombel</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table sibk-table sibk-classroom-table mb-0 align-middle">
                    @if($jurusan === null)
                    <thead><tr><th scope="col">Jurusan</th><th scope="col">Jumlah Rombel</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($groups as $name => $group)
                            <tr>
                                <td class="fw-semibold">{{ $name }}</td>
                                <td>{{ $group->count() }}</td>
                                <td><a class="btn btn-outline-primary sibk-icon-button" href="{{ route('data-master.classrooms.index', ['jurusan' => $name]) }}" aria-label="Lihat daftar rombel jurusan {{ $name }}" title="Lihat rombel jurusan {{ $name }}">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                                </a></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada rombel. Tambahkan rombel sekolah untuk memulai.</td></tr>
                        @endforelse
                    </tbody>
                    @else
                    <thead><tr><th scope="col">Nama Rombel</th><th scope="col">Status</th></tr></thead>
                    <tbody>
                        @foreach($groups->get($jurusan) as $classroom)
                            <tr>
                                <td>
                                    <button class="btn btn-link fw-semibold p-0 text-start sibk-classroom-name" type="button"
                                        data-bs-toggle="modal" data-bs-target="#renameClassroomModal"
                                        data-classroom-name="{{ $classroom->name }}"
                                        data-classroom-active="{{ $classroom->is_active ? '1' : '0' }}"
                                        data-classroom-update-url="{{ route('data-master.classrooms.update', $classroom) }}"
                                        aria-label="Ganti nama rombel {{ $classroom->name }}">{{ $classroom->name }}</button>
                                </td>
                                <td>
                                    <form action="{{ route('data-master.classrooms.update', $classroom) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="name" value="{{ $classroom->name }}">
                                        <input type="hidden" name="is_active" value="{{ $classroom->is_active ? '0' : '1' }}">
                                        <input type="hidden" name="jurusan" value="{{ $jurusan }}">
                                        <button class="sibk-account-switch sibk-classroom-switch" type="submit" role="switch"
                                            aria-checked="{{ $classroom->is_active ? 'true' : 'false' }}"
                                            aria-label="Status rombel {{ $classroom->name }}"
                                            title="{{ $classroom->is_active ? 'Nonaktifkan' : 'Aktifkan' }} rombel {{ $classroom->name }}">
                                            <span class="sibk-account-switch__track" aria-hidden="true"><span class="sibk-account-switch__thumb"></span></span>
                                            {{ $classroom->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
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
                    <input type="hidden" name="is_active" value="1">
                    @if($jurusan !== null)<input type="hidden" name="jurusan" value="{{ $jurusan }}">@endif
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="renameClassroomTitle">Ganti Nama Rombel</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="rename-classroom-name">Nama Rombel</label>
                        <input class="form-control" id="rename-classroom-name" name="name" maxlength="100" required>
                        <p class="small text-muted mb-0 mt-2">Nama di tahun ajaran yang sudah selesai tetap tersimpan.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan Nama</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
