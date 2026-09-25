@extends('layouts.app-2')

@section('page-title', 'Konflik e-Tatib - Ruang BK')

@section('body')
    <div
        class="sibk-dashboard"
        data-page-id="PG-501-ETATIB-CONFLICTS"
        data-etatib-mapping-page
        data-candidate-url="{{ route('data-master.etatib.candidates') }}"
    >
        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy">
                <a
                    href="{{ route('data-master.index') }}"
                    class="text-decoration-none small"
                >
                    &larr; Data Master
                </a>
                <h1>Konflik e-Tatib</h1>
                <p>Cocokkan data e-Tatib dengan daftar murid sekolah.</p>
            </div>
        </div>

        <nav class="nav nav-tabs mb-4" aria-label="Pengelolaan konflik e-Tatib">
            <a
                class="nav-link @if($tab === 'conflicts') active @endif"
                href="{{ route('data-master.etatib.conflicts.index') }}"
                @if($tab === 'conflicts') aria-current="page" @endif
            >
                Belum Cocok
                <span class="badge text-bg-secondary ms-1">{{ $conflicts->total() }}</span>
            </a>
            <a
                class="nav-link @if($tab === 'mappings') active @endif"
                href="{{ route('data-master.etatib.conflicts.index', ['tab' => 'mappings']) }}"
                @if($tab === 'mappings') aria-current="page" @endif
            >
                Pencocokan Manual
                <span class="badge text-bg-secondary ms-1">{{ $mappings->total() }}</span>
            </a>
        </nav>

        @if($tab === 'conflicts')
            <section class="sibk-panel" aria-labelledby="unmatched-etatib-title">
                <div class="sibk-panel__header p-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-1" id="unmatched-etatib-title">Identitas Belum Cocok</h2>
                    <p class="text-muted small mb-0">
                        Pencocokan otomatis dilakukan jika NISN dan nama sama.
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="table sibk-table mb-0">
                        <thead>
                            <tr>
                                <th>Identitas Sumber</th>
                                <th>Kelas Contoh</th>
                                <th>Alasan</th>
                                <th>Pelanggaran</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($conflicts as $conflict)
                                <tr>
                                    <td>
                                        <strong class="d-block">{{ $conflict['name'] }}</strong>
                                        <span class="text-muted small">NISN {{ $conflict['nisn'] }}</span>
                                    </td>
                                    <td>{{ $conflict['classroom'] ?? '-' }}</td>
                                    <td>{{ collect($conflict['reasons'])->unique()->first() }}</td>
                                    <td>{{ number_format($conflict['record_count'], 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-primary btn-sm"
                                            type="button"
                                            data-etatib-map-open
                                            data-action="{{ route('data-master.etatib.mappings.store', $conflict['issue_id']) }}"
                                            data-method="post"
                                            data-source-nisn="{{ $conflict['nisn'] }}"
                                            data-source-name="{{ $conflict['name'] }}"
                                            data-source-classroom="{{ $conflict['classroom'] ?? '-' }}"
                                        >
                                            Cocokkan
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Tidak ada konflik identitas e-Tatib yang perlu dicocokkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if($conflicts->hasPages())
                <div class="mt-3">{{ $conflicts->links() }}</div>
            @endif
        @else
            <section class="sibk-panel" aria-labelledby="etatib-mappings-title">
                <div class="sibk-panel__header p-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-1" id="etatib-mappings-title">Pencocokan Manual Aktif</h2>
                    <p class="text-muted small mb-0">
                        Keputusan ini dipakai kembali untuk pelanggaran dengan pasangan NISN dan nama sumber yang sama.
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="table sibk-table mb-0">
                        <thead>
                            <tr>
                                <th>Identitas Sumber</th>
                                <th>Murid Master</th>
                                <th>Dicocokkan Oleh</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mappings as $mapping)
                                <tr>
                                    <td>
                                        <strong class="d-block">{{ $mapping->source_name }}</strong>
                                        <span class="text-muted small">NISN {{ $mapping->source_nisn }}</span>
                                    </td>
                                    <td>
                                        <strong class="d-block">{{ $mapping->student->name }}</strong>
                                        <span class="text-muted small">NISN {{ $mapping->student->nisn }}</span>
                                    </td>
                                    <td>
                                        {{ $mapping->mapper?->name ?? 'Sistem' }}
                                        <span class="d-block text-muted small">
                                            {{ $mapping->mapped_at->locale('id')->translatedFormat('d M Y, H.i') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                            <button
                                                class="btn btn-outline-primary btn-sm"
                                                type="button"
                                                data-etatib-map-open
                                                data-action="{{ route('data-master.etatib.mappings.update', $mapping) }}"
                                                data-method="patch"
                                                data-source-nisn="{{ $mapping->source_nisn }}"
                                                data-source-name="{{ $mapping->source_name }}"
                                                data-source-classroom="-"
                                            >
                                                Ubah
                                            </button>
                                            <form
                                                action="{{ route('data-master.etatib.mappings.destroy', $mapping) }}"
                                                method="POST"
                                                data-confirm-submit
                                                data-confirm-message="Batalkan pencocokan ini dan terapkan kembali aturan otomatis?"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="confirmed" value="1">
                                                <button class="btn btn-outline-danger btn-sm" type="submit">
                                                    Batalkan
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Belum ada pencocokan manual aktif.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if($mappings->hasPages())
                <div class="mt-3">{{ $mappings->links() }}</div>
            @endif
        @endif
    </div>

    <div
        class="modal fade"
        id="etatib-mapping-modal"
        tabindex="-1"
        aria-labelledby="etatib-mapping-title"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" data-etatib-map-form>
                    @csrf
                    <input type="hidden" name="_method" value="PATCH" disabled data-etatib-map-method>
                    <input type="hidden" name="student_id" data-etatib-student-id>
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title fs-5" id="etatib-mapping-title">Cocokkan Identitas e-Tatib</h2>
                            <p class="text-muted small mb-0">Pilih murid yang sesuai dari daftar sekolah.</p>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Tutup"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <span class="text-muted small d-block">Data sumber e-Tatib</span>
                                <strong class="d-block" data-etatib-source-name></strong>
                                <span class="small" data-etatib-source-nisn></span>
                                <span class="small d-block" data-etatib-source-classroom></span>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="etatib_candidate_search">Cari murid master</label>
                                <div class="input-group">
                                    <input
                                        class="form-control"
                                        id="etatib_candidate_search"
                                        placeholder="Nama, NISN, atau rombel"
                                        autocomplete="off"
                                        data-etatib-candidate-search
                                    >
                                    <button
                                        class="btn btn-outline-primary"
                                        type="button"
                                        data-etatib-candidate-submit
                                    >
                                        Cari
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded" data-etatib-candidate-results>
                            <p class="text-muted text-center small p-4 mb-0">
                                Masukkan minimal dua karakter untuk mencari murid.
                            </p>
                        </div>

                        <div class="form-check mt-4">
                            <input
                                class="form-check-input"
                                id="etatib_mapping_confirmed"
                                name="confirmed"
                                type="checkbox"
                                value="1"
                                required
                            >
                            <label class="form-check-label" for="etatib_mapping_confirmed">
                                Saya sudah memeriksa identitas sumber dan murid master yang dipilih.
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button class="btn btn-primary" type="submit" data-etatib-map-save disabled>
                            Simpan Pencocokan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
