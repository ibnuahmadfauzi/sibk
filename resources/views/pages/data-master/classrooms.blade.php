@extends('layouts.app-2')

@section('page-title', 'Data Kelas - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <h1>Data Kelas</h1>
                <p>Kelola rombel yang disiapkan otomatis saat tahun ajaran baru dibuat.</p>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

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
            <div class="sibk-panel__body p-4">
                <h2 class="fs-5 mb-2" id="classroom-list-title">Daftar rombel</h2>
                <p class="text-muted mb-3">Perubahan nama atau status berlaku pada tahun berjalan dan Persiapan. Riwayat tahun selesai tetap tersimpan.</p>
                @forelse($classrooms as $classroom)
                    <form class="row g-3 align-items-end border-top py-3" action="{{ route('data-master.classrooms.update', $classroom) }}" method="POST">
                        @csrf @method('PATCH')
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="classroom-{{ $classroom->id }}">Nama rombel</label>
                            <input class="form-control" id="classroom-{{ $classroom->id }}" name="name" value="{{ $classroom->name }}" maxlength="100" required>
                        </div>
                        <div class="col-7 col-md-3">
                            <label class="form-label" for="classroom-status-{{ $classroom->id }}">Status</label>
                            <select class="form-select" id="classroom-status-{{ $classroom->id }}" name="is_active">
                                <option value="1" @selected($classroom->is_active)>Aktif</option>
                                <option value="0" @selected(! $classroom->is_active)>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-5 col-md-3"><button class="btn btn-outline-primary w-100" type="submit">Simpan</button></div>
                    </form>
                @empty
                    <p class="text-muted mb-0">Belum ada rombel. Tambahkan rombel sekolah untuk memulai.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
