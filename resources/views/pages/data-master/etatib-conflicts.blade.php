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
            <x-notification-toast>{{ session('success') }}</x-notification-toast>
        @endif

        @if($errors->any())
            <x-notification-toast tone="error">{{ $errors->first() }}</x-notification-toast>
        @endif

        <div class="sibk-page-header d-flex align-items-start gap-3 mb-4">
            <a href="{{ route('data-master.index') }}" class="btn btn-icon btn-light text-primary flex-shrink-0" aria-label="Kembali ke Data Master" title="Kembali ke Data Master">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg>
            </a>
            <div class="sibk-page-header__copy m-0">
                <h1>Konflik e-Tatib</h1>
                <p>Cocokkan data e-Tatib dengan daftar murid sekolah.</p>
            </div>
        </div>

        <nav class="nav nav-pills gap-2 mb-4" aria-label="Pengelolaan konflik e-Tatib">
            <a
                class="nav-link @if($tab === 'conflicts') active @endif"
                href="{{ route('data-master.etatib.conflicts.index', request()->only('search')) }}"
                @if($tab === 'conflicts') aria-current="page" @endif
            >
                Belum Cocok
                <span class="badge text-bg-secondary ms-1">{{ $conflicts->total() }}</span>
            </a>
            <a
                class="nav-link @if($tab === 'mappings') active @endif"
                href="{{ route('data-master.etatib.conflicts.index', array_merge(request()->only('search'), ['tab' => 'mappings'])) }}"
                @if($tab === 'mappings') aria-current="page" @endif
            >
                Pencocokan Manual
                <span class="badge text-bg-secondary ms-1">{{ $mappings->total() }}</span>
            </a>
        </nav>

        <form class="mb-3" method="GET" action="{{ route('data-master.etatib.conflicts.index') }}" role="search">
            @if($tab === 'mappings')<input type="hidden" name="tab" value="mappings">@endif
            <label class="visually-hidden" for="etatib-conflict-search">Cari nama atau NISN</label>
            <div class="input-group">
                <input class="form-control" id="etatib-conflict-search" name="search" type="search" value="{{ request('search') }}" placeholder="Cari nama atau NISN...">
                <button class="btn btn-outline-primary" type="submit">Cari</button>
            </div>
        </form>

        @if($tab === 'conflicts')
            <section class="sibk-panel" aria-label="Belum Cocok">
                <div class="table-responsive">
                    <table class="table sibk-table mb-0">
                        <thead>
                            <tr>
                                <x-sort-header name="name" label="Identitas Sumber" />
                                <x-sort-header name="classroom" label="Rombel Sumber" />
                                <th>Alasan</th>
                                <x-sort-header name="record_count" label="Pelanggaran" />
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
                                            class="btn btn-sm p-0 sibk-icon-button sibk-report-control"
                                            type="button"
                                            aria-label="Cocokkan"
                                            title="Cocokkan"
                                            data-etatib-map-open
                                            data-action="{{ route('data-master.etatib.mappings.store', $conflict['issue_id']) }}"
                                            data-method="post"
                                            data-source-nisn="{{ $conflict['nisn'] }}"
                                            data-source-name="{{ $conflict['name'] }}"
                                            data-source-classroom="{{ $conflict['classroom'] ?? '-' }}"
                                        >
                                            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1M14 11a5 5 0 0 0-7.1 0l-2 2a5 5 0 0 0 7.1 7.1l1.1-1.1"/></svg>
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
                <div class="mt-3">{{ $conflicts->links('pagination.data-master') }}</div>
            @endif
        @else
            <section class="sibk-panel" aria-label="Pencocokan Manual">
                <div class="table-responsive">
                    <table class="table sibk-table mb-0">
                        <thead>
                            <tr>
                                <x-sort-header name="source_name" label="Identitas Sumber" sort-param="mapping_sort" direction-param="mapping_direction" page-param="mapping_page" />
                                <x-sort-header name="student_name" label="Murid Master" sort-param="mapping_sort" direction-param="mapping_direction" page-param="mapping_page" />
                                <th>Dicocokkan Oleh</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mappings as $mapping)
                                <tr>
                                    <td>
                                        <strong class="d-block">{{ \App\Support\StudentName::display($mapping->source_name) }}</strong>
                                        <span class="text-muted small">NISN {{ $mapping->source_nisn }}</span>
                                    </td>
                                    <td>
                                        <strong class="d-block">{{ \App\Support\StudentName::display($mapping->student->name) }}</strong>
                                        <span class="text-muted small">NISN {{ $mapping->student->nisn }}</span>
                                    </td>
                                    <td>
                                        {{ $mapping->mapper?->name ?? 'Sistem' }}
                                        <span class="d-block text-muted small">
                                            {{ $mapping->mapped_at->locale('id')->translatedFormat('d M Y, H.i') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex justify-content-end gap-2">
                                            <button
                                                class="btn btn-sm p-0 sibk-icon-button sibk-report-control"
                                                type="button"
                                                aria-label="Ubah Pencocokan"
                                                title="Ubah Pencocokan"
                                                data-etatib-map-open
                                                data-action="{{ route('data-master.etatib.mappings.update', $mapping) }}"
                                                data-method="patch"
                                                data-source-nisn="{{ $mapping->source_nisn }}"
                                                data-source-name="{{ $mapping->source_name }}"
                                                data-source-classroom="-"
                                            >
                                                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m4 20 4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20ZM14 7l3 3"/></svg>
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
                                                <button class="btn btn-sm p-0 sibk-icon-button sibk-report-control" type="submit" aria-label="Batalkan Pencocokan" title="Batalkan Pencocokan">
                                                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1M14 11a5 5 0 0 0-7.1 0l-2 2a5 5 0 0 0 7.1 7.1l1.1-1.1M4 4l16 16"/></svg>
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
                <div class="mt-3">{{ $mappings->links('pagination.data-master') }}</div>
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

                        <div data-etatib-candidate-results>
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
