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
            <div class="sibk-assignment-toast-region" aria-live="polite" aria-atomic="true">
                <div class="toast sibk-assignment-toast" id="dataMasterToast" role="status">
                    <div class="toast-body d-flex align-items-start gap-2">
                        <span class="sibk-assignment-toast__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6" /></svg>
                        </span>
                        <div class="flex-grow-1">
                            <strong class="d-block">Perubahan berhasil</strong>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="toast" aria-label="Tutup pemberitahuan"></button>
                    </div>
                </div>
            </div>
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
                    <h2 class="sibk-panel__title" id="classroom-list-title">Daftar Rombel</h2>
                    <p class="sibk-panel__subtitle">Klik nama untuk menggantinya. Gunakan tombol status untuk mengaktifkan atau menonaktifkan rombel.</p>
                </div>
                <span class="sibk-badge sibk-badge--primary">{{ $classrooms->count() }} rombel</span>
            </div>
            <div class="table-responsive">
                <table class="table sibk-table mb-0 align-middle">
                    <thead><tr><th scope="col">Nama Rombel</th><th scope="col">Status</th></tr></thead>
                    <tbody>
                        @forelse($classrooms as $classroom)
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
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">Belum ada rombel. Tambahkan rombel sekolah untuk memulai.</td></tr>
                        @endforelse
                    </tbody>
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
