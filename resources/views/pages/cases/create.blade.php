@extends('layouts.app-2')

@section('page-title', 'Catat Permasalahan - Ruang BK')

@section('body')
    @php
        $selectedSourceId = old('case_source_id', request('case_source_id', $temporaryNisnFilter ? $caseSources->firstWhere('code', 'e_tatib')?->id : ($caseSources->firstWhere('code', 'e_tatib')?->id ?? $caseSources->first()?->id)));
        $selectedSource = $caseSources->firstWhere('id', $selectedSourceId) ?? $caseSources->first();
        $selectedSourceCode = $selectedSource?->code ?? 'e_tatib';
        $isEtatibInitial = $selectedSourceCode === 'e_tatib';
        $isRujukanInitial = $selectedSourceCode === 'rujukan';
        $userDisplayName = auth()->user()?->name ?? 'Guru BK';
    @endphp

    <div class="sibk-dashboard py-3" data-page-id="PG-102">
        {{-- Header Halaman --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('cases.index') }}" class="btn btn-light rounded-circle shadow-sm border d-flex align-items-center justify-content-center text-dark flex-shrink-0" style="width: 44px; height: 44px;" aria-label="Kembali ke daftar kasus">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="h4 fw-bold mb-1 text-dark">Catat Permasalahan</h1>
                    <p class="text-secondary small mb-0">Catat informasi layanan BK secara terstruktur dan lengkap.</p>
                </div>
            </div>

        </div>

        @if($errors->any())
            <div class="alert alert-danger shadow-sm border-0 rounded-3 mb-4" role="alert">
                <div class="d-flex align-items-center gap-2 fw-semibold mb-1">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                    </svg>
                    Terdapat kesalahan pengisian data:
                </div>
                <ul class="mb-0 ps-4 small">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form Utama Penyimpanan Kasus --}}
        <form action="{{ route('cases.store') }}" method="POST" id="case-create-form" data-autosave-form="case" data-autosave-record="new">
            @csrf

            {{-- Hidden Inputs --}}
            <input type="hidden" name="student_id" id="hidden_student_id" value="{{ old('student_id', $preselectedStudentId) }}">
            <input type="hidden" name="etatib_record_id" id="hidden_etatib_record_id" value="{{ old('etatib_record_id', (old('etatib_record_ids') ? old('etatib_record_ids')[0] : '')) }}">

            <div class="alert alert-info d-flex align-items-start gap-2 d-none" data-draft-restored role="status">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="text-info flex-shrink-0 mt-1" aria-hidden="true"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/></svg>
                <div class="small"><strong>Draft dipulihkan.</strong> Isian form ini berasal dari draft tersimpan di perangkat Anda dan belum tersimpan ke server. Hapus draft jika isian ini tidak diperlukan.</div>
            </div>

            {{-- STEP 1: Sumber Permasalahan --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.95rem;">
                            1
                        </div>
                        <div>
                            <h2 class="h5 fw-bold text-dark mb-0">Sumber Permasalahan</h2>
                            <p class="text-secondary small mb-0 mt-1">Pilih sumber dari mana informasi permasalahan ini berasal.</p>
                        </div>
                    </div>

                    <div class="row g-3 align-items-center pt-2">
                        <div class="col-12 col-md-5">
                            <label for="sumber" class="form-label text-dark fw-semibold small mb-2">Sumber</label>
                            <select class="form-select py-2 border-secondary-subtle rounded-3" id="sumber" name="case_source_id" required>
                                @foreach($caseSources as $source)
                                    <option value="{{ $source->id }}" data-code="{{ $source->code }}" @selected((string) $selectedSourceId === (string) $source->id)>
                                        {{ $source->label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-7">
                            <div class="p-3 rounded-3 border d-flex align-items-start gap-3" style="background-color: #eff6ff; border-color: #dbeafe !important;">
                                <svg class="text-primary flex-shrink-0 mt-1" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                                </svg>
                                <span class="small text-primary-emphasis" id="source-info-text">
                                    Jika sumber dipilih "e-Tatib", Anda dapat mencari data langsung dari e-Tatib: berdasarkan NISN atau nama.
                                </span>
                            </div>
                        </div>

                        {{-- Pihak Perujuk bila sumber Rujukan --}}
                        <div class="col-12 {{ ! $isRujukanInitial ? 'd-none' : '' }}" id="referrer-group">
                            <label for="referrer" class="form-label text-dark fw-semibold small mb-2">Pihak Perujuk</label>
                            <input class="form-control py-2 border-secondary-subtle rounded-3" id="referrer" name="referrer" value="{{ old('referrer') }}" placeholder="Tuliskan nama atau pihak yang merujuk murid">
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 2 (KHUSUS e-TATIB): Cari Data e-Tatib --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 {{ ! $isEtatibInitial ? 'd-none' : '' }}" id="etatib-search-section">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.95rem;">
                            2
                        </div>
                        <div>
                            <h2 class="h5 fw-bold text-dark mb-0">Cari Data e-Tatib</h2>
                            <p class="text-secondary small mb-0 mt-1">Cari siswa pada e-Tatib menggunakan NISN atau nama. Pilih data untuk mengisi informasi murid dan pelanggaran.</p>
                        </div>
                    </div>

                    {{-- Search Box e-Tatib --}}
                    <div class="row g-2 pt-2 mb-3">
                        <div class="col-12 col-md-10">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-secondary-subtle pe-1 rounded-start-3">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-muted">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </span>
                                <input type="text" class="form-control border-start-0 border-end-0 border-secondary-subtle py-2" id="etatib_search_input" value="{{ $search }}" placeholder="Cari NISN atau nama murid...">
                                <button type="button" class="btn btn-outline-secondary border-start-0 border-secondary-subtle rounded-end-3 bg-white text-muted" id="etatib_clear_btn" style="display: none;" title="Hapus pencarian">
                                    &times;
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="button" class="btn btn-primary w-100 py-2 rounded-3 fw-medium" id="etatib_search_btn">
                                Cari
                            </button>
                        </div>
                    </div>

                    {{-- List Hasil Pencarian e-Tatib --}}
                    <div id="etatib_results_container" class="d-flex flex-column gap-2 mb-3">
                        {{-- Diisi secara dinamis via JavaScript dari formattedEtatibRecords --}}
                    </div>

                    <div id="etatib_empty_state" class="text-center py-4 text-secondary d-none">
                        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="text-muted mb-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="mb-0 small">Tidak ditemukan data e-Tatib yang sesuai dengan kata kunci.</p>
                    </div>

                    {{-- Pagination Bar e-Tatib --}}
                    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between pt-2 border-top text-secondary small gap-2" id="etatib_pagination_bar">
                        <span id="etatib_count_info">Menampilkan 0 data</span>
                        <div class="d-flex align-items-center gap-1" id="etatib_pagination_controls">
                            {{-- Tombol pagination < 1 2 > --}}
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP DATA MURID (Step 3 untuk e-Tatib, Step 2 untuk Non-e-Tatib) --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4" id="student-data-section">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" id="step-number-student" style="width: 32px; height: 32px; font-size: 0.95rem;">
                            {{ $isEtatibInitial ? '3' : '2' }}
                        </div>
                        <div>
                            <h2 class="h5 fw-bold text-dark mb-0">Data Murid</h2>
                            <p class="text-secondary small mb-0 mt-1" id="step-desc-student">
                                {{ $isEtatibInitial ? 'Data murid terisi otomatis dari data e-Tatib yang dipilih. Anda masih dapat mengubah jika diperlukan.' : 'Lengkapi data murid yang akan dicatat permasalahannya.' }}
                            </p>
                        </div>
                    </div>

                    <div class="row g-3 pt-2">
                        <div class="col-12 col-md-4">
                            <label for="student_nisn" class="form-label text-dark fw-semibold small mb-2">NISN</label>
                            <input type="text" class="form-control py-2 border-secondary-subtle rounded-3 {{ $isEtatibInitial ? 'bg-light text-muted' : '' }}" id="student_nisn" name="temporary_nisn" value="{{ old('temporary_nisn') }}" maxlength="20" inputmode="numeric" placeholder="Masukkan NISN" aria-controls="student_lookup_results" aria-autocomplete="list" aria-expanded="false" {{ $isEtatibInitial ? 'readonly' : 'required' }}>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="student_name" class="form-label text-dark fw-semibold small mb-2">Nama Murid</label>
                            <input type="text" class="form-control py-2 border-secondary-subtle rounded-3 {{ $isEtatibInitial ? 'bg-light text-muted' : '' }}" id="student_name" name="temporary_name" value="{{ old('temporary_name') }}" maxlength="150" placeholder="Masukkan nama murid" aria-controls="student_lookup_results" aria-autocomplete="list" aria-expanded="false" {{ $isEtatibInitial ? 'readonly' : 'required' }}>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="student_classroom" class="form-label text-dark fw-semibold small mb-2">Rombel</label>

                            {{-- Tampilan Rombel e-Tatib (Readonly Input) --}}
                            <input type="text" class="form-control py-2 border-secondary-subtle rounded-3 bg-light text-muted {{ ! $isEtatibInitial ? 'd-none' : '' }}" id="etatib_classroom_display" placeholder="Rombel murid" readonly>

                            {{-- Tampilan Rombel Non-e-Tatib (Dropdown Select) --}}
                            <select class="form-select py-2 border-secondary-subtle rounded-3 {{ $isEtatibInitial ? 'd-none' : '' }}" id="manual_classroom_select" name="temporary_classroom_id">
                                <option value="">Pilih rombel yang Anda ampu</option>
                                @foreach($temporaryClassrooms as $classroom)
                                    <option value="{{ $classroom->id }}" @selected((string) old('temporary_classroom_id') === (string) $classroom->id)>
                                        {{ $classroom->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <div id="student_lookup_results" class="list-group border rounded-3 overflow-hidden d-none" role="listbox" aria-label="Hasil pencarian murid"></div>
                            <p class="small text-secondary mb-0 mt-2" id="student_lookup_hint">
                                Ketik NISN atau nama untuk mencari murid. Jika tidak ditemukan, lanjutkan mengisi data secara manual.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 4 (KHUSUS e-TATIB): Data Pelanggaran e-Tatib --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4 {{ ! $isEtatibInitial ? 'd-none' : '' }}" id="etatib-violation-section">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.95rem;">
                            4
                        </div>
                        <div>
                            <h2 class="h5 fw-bold text-dark mb-0">Data Pelanggaran e-Tatib</h2>
                            <p class="text-secondary small mb-0 mt-1">Data pelanggaran terisi otomatis dari data e-Tatib yang dipilih.</p>
                        </div>
                    </div>

                    {{-- Container Informasi Pelanggaran Readonly --}}
                    <div class="p-4 rounded-3 border" style="background-color: #f0f7ff; border-color: #dbeafe !important;">
                        <div class="row g-4">
                            <div class="col-12 col-md-4">
                                <div class="text-secondary small mb-1">Pelanggaran</div>
                                <div class="fw-bold text-dark fs-6 text-break" id="view_violation_type">-</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="text-secondary small mb-1">Tanggal Pelanggaran</div>
                                <div class="d-flex align-items-center gap-2 fw-semibold text-dark" id="view_occurred_at_container">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-muted flex-shrink-0">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span id="view_occurred_at">-</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="text-secondary small mb-1">Poin Pelanggaran</div>
                                <div class="fw-bold text-dark fs-6" id="view_points">-</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-secondary small mb-1">Kategori</div>
                                <div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold fs-7" id="view_category">
                                        -
                                    </span>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="text-secondary small mb-1">Dicatat oleh</div>
                                <div class="fw-semibold text-dark" id="view_recorded_by">-</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="text-secondary small mb-1 d-flex align-items-center gap-1">
                                    <span>Total Poin Siswa</span>
                                    <svg width="14" height="14" fill="currentColor" class="text-muted" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                                    </svg>
                                </div>
                                <div class="fw-bold text-dark fs-6" id="view_total_points">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP INFORMASI PERMASALAHAN (Step 5 untuk e-Tatib, Step 3 untuk Non-e-Tatib) --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4" id="case-info-section">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" id="step-number-info" style="width: 32px; height: 32px; font-size: 0.95rem;">
                            {{ $isEtatibInitial ? '5' : '3' }}
                        </div>
                        <div>
                            <h2 class="h5 fw-bold text-dark mb-0">Informasi Permasalahan</h2>
                            <p class="text-secondary small mb-0 mt-1">Lengkapi informasi dasar permasalahan yang ditangani.</p>
                        </div>
                    </div>

                    <div class="row g-4 pt-2">
                        <div class="col-12 col-md-6">
                            <label for="service_field_id" class="form-label text-dark fw-semibold small mb-2">Jenis Masalah</label>
                            <select class="form-select py-2 border-secondary-subtle rounded-3" id="service_field_id" name="service_field_id" required>
                                <option value="">Pilih jenis masalah</option>
                                @foreach($serviceFields as $field)
                                    <option value="{{ $field->id }}" @selected((string) old('service_field_id') === (string) $field->id)>
                                        {{ $field->label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="service_date" class="form-label text-dark fw-semibold small mb-2">Tanggal Layanan</label>
                            <div class="input-group">
                                <input type="date" class="form-control py-2 border-secondary-subtle rounded-3" id="service_date" name="service_date" value="{{ old('service_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP CATATAN PERMASALAHAN (Step 6 untuk e-Tatib, Step 4 untuk Non-e-Tatib) --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4" id="case-notes-section">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="step-badge rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" id="step-number-notes" style="width: 32px; height: 32px; font-size: 0.95rem;">
                            {{ $isEtatibInitial ? '6' : '4' }}
                        </div>
                        <div>
                            <h2 class="h5 fw-bold text-dark mb-0">Catatan Permasalahan</h2>
                            <p class="text-secondary small mb-0 mt-1">Tuliskan informasi yang diperlukan untuk mencatat dan menangani permasalahan.</p>
                        </div>
                    </div>

                    <div class="row g-3 pt-2">
                        <div class="col-12 col-md-4">
                            <label for="initial_info" class="form-label text-dark fw-semibold small mb-2">Latar Belakang</label>
                            <textarea class="form-control border-secondary-subtle rounded-3 p-3 small" id="initial_info" name="initial_info" rows="5" placeholder="Jelaskan kondisi, kronologi, atau alasan siswa mendapatkan layanan BK." required>{{ old('initial_info') }}</textarea>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="initial_action" class="form-label text-dark fw-semibold small mb-2">Penanganan</label>
                            <textarea class="form-control border-secondary-subtle rounded-3 p-3 small" id="initial_action" name="initial_action" rows="5" placeholder="Tuliskan tindakan yang dilakukan dalam menangani permasalahan ini." required>{{ old('initial_action') }}</textarea>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="resolution_summary" class="form-label text-dark fw-semibold small mb-2">Ringkasan</label>
                            <textarea class="form-control border-secondary-subtle rounded-3 p-3 small" id="resolution_summary" name="resolution_summary" rows="5" placeholder="Tuliskan ringkasan permasalahan dan penanganan secara singkat.">{{ old('resolution_summary') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Actions --}}
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-3 pb-5">
                <span class="small text-muted me-auto" data-draft-status aria-live="polite"></span>
                <button type="button" class="btn btn-light px-4 py-2 rounded-3 fw-medium" data-clear-draft id="btn-clear-draft">
                    Hapus Draft
                </button>
                <a href="{{ route('cases.index', ['tab' => 'kasus']) }}" class="btn btn-outline-primary px-4 py-2 rounded-3 fw-medium">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm" id="btn-save-case">
                    Simpan
                </button>
            </div>
        </form>
    </div>
@endsection

@section('extra-javascript')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Data master e-Tatib yang diformat dari server
            const etatibRecordsData = @json($formattedEtatibRecords);
            const studentLookupData = @json($studentLookupData);

            // Elemen DOM
            const form = document.getElementById('case-create-form');
            const sumberSelect = document.getElementById('sumber');
            const sourceInfoText = document.getElementById('source-info-text');
            const referrerGroup = document.getElementById('referrer-group');
            const referrerInput = document.getElementById('referrer');

            const etatibSearchSection = document.getElementById('etatib-search-section');
            const etatibViolationSection = document.getElementById('etatib-violation-section');

            const stepNumberStudent = document.getElementById('step-number-student');
            const stepDescStudent = document.getElementById('step-desc-student');
            const stepNumberInfo = document.getElementById('step-number-info');
            const stepNumberNotes = document.getElementById('step-number-notes');

            const studentNisnInput = document.getElementById('student_nisn');
            const studentNameInput = document.getElementById('student_name');
            const etatibClassroomDisplay = document.getElementById('etatib_classroom_display');
            const manualClassroomSelect = document.getElementById('manual_classroom_select');
            const studentLookupResults = document.getElementById('student_lookup_results');
            const studentLookupHint = document.getElementById('student_lookup_hint');

            const hiddenStudentId = document.getElementById('hidden_student_id');
            const hiddenEtatibRecordId = document.getElementById('hidden_etatib_record_id');

            // Elemen Detail Pelanggaran
            const viewViolationType = document.getElementById('view_violation_type');
            const viewOccurredAt = document.getElementById('view_occurred_at');
            const viewPoints = document.getElementById('view_points');
            const viewCategory = document.getElementById('view_category');
            const viewRecordedBy = document.getElementById('view_recorded_by');
            const viewTotalPoints = document.getElementById('view_total_points');

            // Elemen Search e-Tatib
            const searchInput = document.getElementById('etatib_search_input');
            const searchBtn = document.getElementById('etatib_search_btn');
            const clearBtn = document.getElementById('etatib_clear_btn');
            const resultsContainer = document.getElementById('etatib_results_container');
            const emptyState = document.getElementById('etatib_empty_state');
            const countInfo = document.getElementById('etatib_count_info');
            const paginationControls = document.getElementById('etatib_pagination_controls');

            // State Pencarian & Pilihan e-Tatib
            let currentSearchTerm = (searchInput?.value || '').trim().toLowerCase();
            let currentPage = 1;
            const pageSize = 3; // Menampilkan 3 data per halaman seperti pada Gambar 1
            let selectedRecordId = hiddenEtatibRecordId?.value ? parseInt(hiddenEtatibRecordId.value, 10) : null;

            // Jika belum ada yang dipilih tetapi ada data e-Tatib, otomatis pilih data pertama jika sumber = e-Tatib
            if (!selectedRecordId && etatibRecordsData.length > 0 && isEtatibSelected()) {
                selectedRecordId = etatibRecordsData[0].id;
                hiddenEtatibRecordId.value = selectedRecordId;
            }

            function isEtatibSelected() {
                const opt = sumberSelect?.selectedOptions[0];
                const code = opt?.dataset?.code || '';
                const text = (opt?.text || '').trim().toLowerCase();
                return code === 'e_tatib' || text === 'e-tatib';
            }

            function isRujukanSelected() {
                const opt = sumberSelect?.selectedOptions[0];
                const code = opt?.dataset?.code || '';
                const text = (opt?.text || '').trim().toLowerCase();
                return code === 'rujukan' || text === 'rujukan';
            }

            // Update Tampilan Berdasarkan Sumber Terpilih
            function updateSourceState() {
                const isEtatib = isEtatibSelected();
                const isRujukan = isRujukanSelected();
                const selectedText = (sumberSelect?.selectedOptions[0]?.text || '').trim();

                // 1. Alert Box Info
                if (sourceInfoText) {
                    if (isEtatib) {
                        sourceInfoText.textContent = 'Jika sumber dipilih "e-Tatib", Anda dapat mencari data langsung dari e-Tatib: berdasarkan NISN atau nama.';
                    } else {
                        sourceInfoText.textContent = `Anda telah memilih "${selectedText}". Cari murid lewat NISN atau nama, atau lanjutkan mengisi manual jika datanya belum tersedia.`;
                    }
                }

                // 2. Referrer Field
                if (referrerGroup) {
                    referrerGroup.classList.toggle('d-none', !isRujukan);
                    if (!isRujukan && referrerInput) {
                        referrerInput.value = '';
                    }
                }

                // 3. Tampilkan/Sembunyikan Bagian e-Tatib
                if (etatibSearchSection) etatibSearchSection.classList.toggle('d-none', !isEtatib);
                if (etatibViolationSection) etatibViolationSection.classList.toggle('d-none', !isEtatib);
                if (studentLookupHint) studentLookupHint.classList.toggle('d-none', isEtatib);
                if (isEtatib) hideStudentLookup();

                // 4. Nomor Urut Langkah (Step Numbering)
                if (isEtatib) {
                    if (stepNumberStudent) stepNumberStudent.textContent = '3';
                    if (stepDescStudent) stepDescStudent.textContent = 'Data murid terisi otomatis dari data e-Tatib yang dipilih. Anda masih dapat mengubah jika diperlukan.';
                    if (stepNumberInfo) stepNumberInfo.textContent = '5';
                    if (stepNumberNotes) stepNumberNotes.textContent = '6';

                    // Mode Readonly untuk Data Murid
                    if (studentNisnInput) {
                        studentNisnInput.readOnly = true;
                        studentNisnInput.classList.add('bg-light', 'text-muted');
                    }
                    if (studentNameInput) {
                        studentNameInput.readOnly = true;
                        studentNameInput.classList.add('bg-light', 'text-muted');
                    }
                    if (etatibClassroomDisplay) etatibClassroomDisplay.classList.remove('d-none');
                    if (manualClassroomSelect) {
                        manualClassroomSelect.classList.add('d-none');
                        manualClassroomSelect.removeAttribute('required');
                    }

                    // Terapkan data e-Tatib terpilih
                    applySelectedEtatibRecord();
                } else {
                    if (stepNumberStudent) stepNumberStudent.textContent = '2';
                    if (stepDescStudent) stepDescStudent.textContent = 'Lengkapi data murid yang akan dicatat permasalahannya.';
                    if (stepNumberInfo) stepNumberInfo.textContent = '3';
                    if (stepNumberNotes) stepNumberNotes.textContent = '4';

                    // Mode Manual Input untuk Data Murid
                    if (studentNisnInput) {
                        studentNisnInput.readOnly = false;
                        studentNisnInput.classList.remove('bg-light', 'text-muted');
                        studentNisnInput.setAttribute('required', 'required');
                    }
                    if (studentNameInput) {
                        studentNameInput.readOnly = false;
                        studentNameInput.classList.remove('bg-light', 'text-muted');
                        studentNameInput.setAttribute('required', 'required');
                    }
                    if (etatibClassroomDisplay) etatibClassroomDisplay.classList.add('d-none');
                    if (manualClassroomSelect) {
                        manualClassroomSelect.classList.remove('d-none');
                        manualClassroomSelect.setAttribute('required', 'required');
                    }

                    // Kosongkan referensi e-Tatib
                    if (hiddenEtatibRecordId) hiddenEtatibRecordId.value = '';
                }
            }

            function hideStudentLookup() {
                if (!studentLookupResults) return;

                studentLookupResults.classList.add('d-none');
                studentLookupResults.innerHTML = '';
                studentNisnInput?.setAttribute('aria-expanded', 'false');
                studentNameInput?.setAttribute('aria-expanded', 'false');
            }

            function selectStudent(student) {
                if (hiddenStudentId) hiddenStudentId.value = student.id;
                if (studentNisnInput) studentNisnInput.value = student.nisn || '';
                if (studentNameInput) studentNameInput.value = student.name || '';
                if (manualClassroomSelect) manualClassroomSelect.value = student.classroom_id || '';
                hideStudentLookup();
            }

            function renderStudentLookup(query) {
                if (!studentLookupResults || isEtatibSelected()) {
                    hideStudentLookup();
                    return;
                }

                const term = String(query || '').trim().toLowerCase();
                if (!term) {
                    hideStudentLookup();
                    return;
                }

                const matches = studentLookupData.filter(student =>
                    String(student.nisn || '').toLowerCase().includes(term)
                    || String(student.name || '').toLowerCase().includes(term)
                ).slice(0, 8);

                studentLookupResults.innerHTML = '';
                studentLookupResults.classList.remove('d-none');
                studentNisnInput?.setAttribute('aria-expanded', 'true');
                studentNameInput?.setAttribute('aria-expanded', 'true');

                if (matches.length === 0) {
                    studentLookupResults.innerHTML = '<div class="px-3 py-2 small text-secondary">Murid tidak ditemukan. Silakan lanjutkan isi data secara manual.</div>';
                    return;
                }

                matches.forEach(student => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'list-group-item list-group-item-action px-3 py-2 text-start';
                    option.setAttribute('role', 'option');
                    option.innerHTML = `<span class="d-block fw-semibold text-dark">${escapeHtml(student.name)}</span><span class="small text-secondary">NISN: ${escapeHtml(student.nisn)} &bull; Rombel: ${escapeHtml(student.classroom_name)}</span>`;
                    option.addEventListener('click', () => selectStudent(student));
                    studentLookupResults.appendChild(option);
                });
            }

            // Terapkan Detail Data Record e-Tatib yang Terpilih ke Form
            function applySelectedEtatibRecord() {
                if (!isEtatibSelected()) return;

                const record = etatibRecordsData.find(r => r.id === selectedRecordId);
                if (record) {
                    if (hiddenEtatibRecordId) hiddenEtatibRecordId.value = record.id;
                    if (hiddenStudentId) hiddenStudentId.value = record.student_id || '';

                    // Data Murid Readonly
                    if (studentNisnInput) studentNisnInput.value = record.nisn || '';
                    if (studentNameInput) studentNameInput.value = record.student_name || '';
                    if (etatibClassroomDisplay) etatibClassroomDisplay.value = record.classroom_name || '-';
                    if (manualClassroomSelect && record.classroom_id) {
                        manualClassroomSelect.value = record.classroom_id;
                    }

                    // Data Pelanggaran Readonly
                    if (viewViolationType) viewViolationType.textContent = record.violation_type || '-';
                    if (viewOccurredAt) viewOccurredAt.textContent = record.occurred_at || '-';
                    if (viewPoints) viewPoints.textContent = record.points !== null && record.points !== undefined ? record.points : '-';
                    if (viewCategory) viewCategory.textContent = record.category || 'Ringan';
                    if (viewRecordedBy) viewRecordedBy.textContent = record.recorded_by_name || '-';
                    if (viewTotalPoints) viewTotalPoints.textContent = record.source_total_points !== null && record.source_total_points !== undefined ? record.source_total_points : '-';
                }
            }

            // Render List Hasil e-Tatib
            function renderEtatibResults() {
                if (!resultsContainer) return;

                const filtered = etatibRecordsData.filter(record => {
                    if (!currentSearchTerm) return true;
                    const nisn = (record.nisn || '').toLowerCase();
                    const name = (record.student_name || '').toLowerCase();
                    const violation = (record.violation_type || '').toLowerCase();
                    return nisn.includes(currentSearchTerm) || name.includes(currentSearchTerm) || violation.includes(currentSearchTerm);
                });

                const totalItems = filtered.length;
                const totalPages = Math.ceil(totalItems / pageSize) || 1;
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;

                const startIndex = (currentPage - 1) * pageSize;
                const paginatedItems = filtered.slice(startIndex, startIndex + pageSize);

                resultsContainer.innerHTML = '';

                if (totalItems === 0) {
                    if (emptyState) emptyState.classList.remove('d-none');
                    if (countInfo) countInfo.textContent = 'Menampilkan 0 dari 0 data';
                    if (paginationControls) paginationControls.innerHTML = '';
                    return;
                }

                if (emptyState) emptyState.classList.add('d-none');
                if (countInfo) {
                    countInfo.textContent = `Menampilkan ${paginatedItems.length} dari ${totalItems} data`;
                }

                // Render Card Items
                paginatedItems.forEach(item => {
                    const isSelected = item.id === selectedRecordId;
                    const itemCard = document.createElement('div');
                    itemCard.className = `p-3 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer transition-all ${isSelected ? 'border-primary bg-primary-subtle' : 'border-secondary-subtle bg-white'}`;
                    itemCard.style.cursor = 'pointer';
                    itemCard.style.backgroundColor = isSelected ? '#f0f7ff' : '#ffffff';
                    itemCard.style.borderColor = isSelected ? '#3b82f6' : '#e2e8f0';

                    itemCard.innerHTML = `
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check m-0 p-0 d-flex align-items-center">
                                <input class="form-check-input mt-0 fs-5" type="radio" name="etatib_radio_selector" value="${item.id}" ${isSelected ? 'checked' : ''} style="cursor: pointer;">
                            </div>
                            <div>
                                <div class="fw-bold text-dark fs-6 text-uppercase">${escapeHtml(item.student_name)}</div>
                                <div class="text-secondary small mt-1">NISN: ${escapeHtml(item.nisn)} &nbsp;|&nbsp; Kelas: ${escapeHtml(item.classroom_name)}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end d-none d-sm-block">
                                <div class="fw-bold text-dark fs-6">${escapeHtml(item.violation_type)}</div>
                                <div class="text-secondary small mt-1">${escapeHtml(item.occurred_at)} &bull; ${item.points} poin &bull; ${escapeHtml(item.category)}</div>
                                <div class="text-secondary small">Dicatat oleh ${escapeHtml(item.recorded_by_name)}</div>
                            </div>
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-muted flex-shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    `;

                    // Klik pada card untuk memilih
                    itemCard.addEventListener('click', (e) => {
                        selectedRecordId = item.id;
                        applySelectedEtatibRecord();
                        renderEtatibResults();
                    });

                    resultsContainer.appendChild(itemCard);
                });

                // Render Tombol Pagination
                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                if (!paginationControls) return;
                paginationControls.innerHTML = '';

                if (totalPages <= 1) return;

                // Tombol Prev
                const prevBtn = document.createElement('button');
                prevBtn.type = 'button';
                prevBtn.className = `btn btn-sm ${currentPage === 1 ? 'btn-light text-muted disabled' : 'btn-outline-secondary'}`;
                prevBtn.innerHTML = '&lsaquo;';
                prevBtn.disabled = currentPage === 1;
                prevBtn.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage--;
                        renderEtatibResults();
                    }
                });
                paginationControls.appendChild(prevBtn);

                // Tombol Angka Halaman
                const visiblePageCount = 5;
                const firstPage = Math.min(
                    Math.max(currentPage - Math.floor(visiblePageCount / 2), 1),
                    Math.max(totalPages - visiblePageCount + 1, 1),
                );
                const lastPage = Math.min(firstPage + visiblePageCount - 1, totalPages);
                for (let i = firstPage; i <= lastPage; i++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.type = 'button';
                    pageBtn.className = `btn btn-sm ${i === currentPage ? 'btn-primary text-white fw-bold' : 'btn-outline-secondary'}`;
                    pageBtn.textContent = i;
                    pageBtn.addEventListener('click', () => {
                        currentPage = i;
                        renderEtatibResults();
                    });
                    paginationControls.appendChild(pageBtn);
                }

                // Tombol Next
                const nextBtn = document.createElement('button');
                nextBtn.type = 'button';
                nextBtn.className = `btn btn-sm ${currentPage === totalPages ? 'btn-light text-muted disabled' : 'btn-outline-secondary'}`;
                nextBtn.innerHTML = '&rsaquo;';
                nextBtn.disabled = currentPage === totalPages;
                nextBtn.addEventListener('click', () => {
                    if (currentPage < totalPages) {
                        currentPage++;
                        renderEtatibResults();
                    }
                });
                paginationControls.appendChild(nextBtn);
            }

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Event Listeners
            sumberSelect?.addEventListener('change', () => {
                updateSourceState();
                if (isEtatibSelected()) {
                    renderEtatibResults();
                }
            });

            [studentNisnInput, studentNameInput].forEach(input => {
                input?.addEventListener('input', () => {
                    if (hiddenStudentId) hiddenStudentId.value = '';
                    renderStudentLookup(input.value);
                });
                input?.addEventListener('focus', () => renderStudentLookup(input.value));
                input?.addEventListener('keydown', event => {
                    if (event.key === 'Escape') hideStudentLookup();
                    if (event.key === 'ArrowDown') {
                        const firstOption = studentLookupResults?.querySelector('button');
                        if (firstOption) {
                            event.preventDefault();
                            firstOption.focus();
                        }
                    }
                });
            });

            document.addEventListener('click', event => {
                if (!studentLookupResults?.contains(event.target)
                    && event.target !== studentNisnInput
                    && event.target !== studentNameInput) {
                    hideStudentLookup();
                }
            });

            // Pencarian e-Tatib
            function performSearch() {
                currentSearchTerm = (searchInput?.value || '').trim().toLowerCase();
                currentPage = 1;
                if (clearBtn) {
                    clearBtn.style.display = currentSearchTerm ? 'block' : 'none';
                }
                renderEtatibResults();
            }

            searchBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                performSearch();
            });

            searchInput?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });

            searchInput?.addEventListener('input', () => {
                if (clearBtn) {
                    clearBtn.style.display = searchInput.value ? 'block' : 'none';
                }
                // Pencarian instan
                performSearch();
            });

            clearBtn?.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                if (clearBtn) clearBtn.style.display = 'none';
                performSearch();
            });

            // Tombol Hapus Draft
            const clearDraftBtn = document.getElementById('btn-clear-draft');
            clearDraftBtn?.addEventListener('click', () => {
                if (confirm('Apakah Anda yakin ingin mengosongkan draft ini?')) {
                    if (form) form.reset();
                    selectedRecordId = null;
                    updateSourceState();
                    renderEtatibResults();
                }
            });

            // Inisialisasi awal
            updateSourceState();
            renderEtatibResults();
        });
    </script>
@endsection
