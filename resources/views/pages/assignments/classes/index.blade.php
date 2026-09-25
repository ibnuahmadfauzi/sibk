@extends('layouts.app-2')

@section('page-title', 'Penugasan Kelas - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-401">
        <div class="sibk-page-header">
            <div class="sibk-page-header__copy">
                <h1>Penugasan Kelas</h1>
                <br>
            </div>
        </div>

        @if(session('success'))
            <div class="sibk-assignment-toast-region" aria-live="polite" aria-atomic="true">
                <div class="toast sibk-assignment-toast" id="assignmentSuccessToast" role="status">
                    <div class="toast-body d-flex align-items-start gap-2">
                        <span class="sibk-assignment-toast__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="m5 12 4 4L19 6" />
                            </svg>
                        </span>
                        <div class="flex-grow-1">
                            <strong class="d-block">{{ session('success_title', 'Perubahan berhasil') }}</strong>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button
                            class="btn-close"
                            type="button"
                            data-bs-dismiss="toast"
                            aria-label="Tutup pemberitahuan"
                        ></button>
                    </div>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        @if($canChooseYear)
            <section class="sibk-panel sibk-activation-panel mb-4" aria-labelledby="activation-readiness-title">
                <div class="sibk-panel__body p-4">
                    <h2 class="fs-5" id="activation-readiness-title">Kesiapan Aktivasi</h2>
                    @if($activeYear !== null || $preparationYears->isNotEmpty())
                        <form
                            class="d-flex flex-wrap align-items-end gap-2 mb-3"
                            action="{{ route('assignments.classes.index') }}"
                            method="GET"
                        >
                            <div>
                                <label class="form-label text-white" for="preparation_year_id">Tahun penugasan</label>
                                <select
                                    class="form-select"
                                    id="preparation_year_id"
                                    name="academic_year_id"
                                >
                                    @if($activeYear !== null)
                                        <option value="{{ $activeYear->id }}" @selected($selectedYear?->is($activeYear))>
                                            {{ $activeYear->name }} (Aktif)
                                        </option>
                                    @endif
                                    @foreach($preparationYears as $year)
                                        <option value="{{ $year->id }}" @selected($selectedYear?->is($year))>
                                            {{ $year->name }} (Persiapan)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-primary" type="submit">Tampilkan</button>
                        </form>
                    @endif
                    @if($selectedYear === null)
                        <p class="mb-0">
                            @if($preparationYears->isNotEmpty())
                                Belum ada tahun ajaran aktif. Pilih tahun Persiapan untuk menyiapkan penugasan.
                            @else
                                Belum ada tahun ajaran aktif atau Persiapan. Admin IT perlu membuat tahun ajaran.
                            @endif
                        </p>
                    @elseif($activationReadiness['state'] === 'active')
                        <p class="mb-0">Tahun ajaran ini sudah aktif.</p>
                        @if($previousYear !== null)
                            <button
                                class="btn btn-primary mt-3"
                                type="button"
                                data-bs-toggle="modal"
                                data-bs-target="#restorePreviousYearModal"
                            >
                                Batalkan aktivasi
                            </button>
                        @endif
                    @else
                        @if($activationReadiness['issues'] !== [])
                            <ul class="text-warning-emphasis mb-2">
                                @foreach($activationReadiness['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if($activationReadiness['warnings'] !== [])
                            <ul class="text-info-emphasis mb-2">
                                @foreach($activationReadiness['warnings'] as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if($activationReadiness['ready'])
                            <form
                                action="{{ route('assignments.academic-years.activate', $selectedYear) }}"
                                method="POST"
                            >
                                @csrf
                                <button class="btn btn-primary" type="submit">Aktifkan Tahun Ajaran</button>
                            </form>
                        @elseif($activationReadiness['state'] === 'not_ready')
                            <button class="btn btn-primary" type="button" disabled>Aktifkan Tahun Ajaran</button>
                        @endif
                    @endif
                </div>
            </section>
        @endif

        @if($previousYear !== null)
            <div
                class="modal fade"
                id="restorePreviousYearModal"
                tabindex="-1"
                aria-labelledby="restorePreviousYearTitle"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="restorePreviousYearTitle">Batalkan aktivasi tahun ajaran?</h2>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">
                                {{ $selectedYear->name }} akan berhenti aktif dan {{ $previousYear->name }} kembali aktif.
                            </p>
                            <p class="text-muted mb-0">
                                Akses Guru BK mengikuti penugasan tahun sebelumnya. Riwayat layanan tetap tersimpan.
                                Jika sudah ada aktivitas operasional sejak aktivasi, sistem akan menolak tindakan ini.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                            <form
                                action="{{ route('assignments.academic-years.restore-previous', $selectedYear) }}"
                                method="POST"
                            >
                                @csrf
                                <button class="btn btn-primary" type="submit">Ya, kembalikan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($selectedYear !== null)
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <form
                    class="row g-3 align-items-end"
                    action="{{ route('assignments.classes.index') }}"
                    method="GET"
                >
                    @if($canChooseYear && ! $selectedYear->is_active)
                        <input type="hidden" name="academic_year_id" value="{{ $selectedYear->id }}">
                    @endif
                    <div class="col-12 col-md-5">
                        <label class="form-label" for="search_kelas">Cari kelas</label>
                        <input
                            class="form-control"
                            id="search_kelas"
                            list="class-suggestions"
                            name="search_kelas"
                            type="search"
                            autocomplete="off"
                            placeholder="Ketik nama kelas"
                            value="{{ request('search_kelas') }}"
                        >
                        <datalist id="class-suggestions">
                            @foreach($classSuggestions as $className)
                                <option value="{{ $className }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="status">Status guru</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all">Semua</option>
                            <option value="assigned" @selected(request('status') === 'assigned')>Ditugaskan</option>
                            <option value="unassigned" @selected(request('status') === 'unassigned')>Belum ditugaskan</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <h2 class="fs-5 mb-3">
            Daftar Penugasan {{ $selectedYear->name }}
            <span class="small fw-normal">
                ({{ $selectedYear->is_active ? 'Aktif' : 'Persiapan' }})
            </span>
        </h2>
        <div class="table-responsive">
            <table class="table sibk-table mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Guru</th>
                        <th>Kelas</th>
                        <th>Jumlah Murid</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold">{{ $row['teacher']->name }}</td>
                            <td>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    @foreach($row['classes'] as $classroom)
                                        <div class="sibk-class-chip">
                                            {{ $classroom->name }}
                                            @if($canManage)
                                                <form
                                                    action="{{ route('assignments.classes.destroy', $classroom) }}"
                                                    method="POST"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <input name="user_id" type="hidden" value="{{ $row['teacher']->id }}">
                                                    <button
                                                        class="sibk-class-chip__remove"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#classUnassignModal"
                                                        data-class-name="{{ $classroom->name }}"
                                                        data-teacher-name="{{ $row['teacher']->name }}"
                                                        aria-label="Batalkan penugasan {{ $classroom->name }} dari {{ $row['teacher']->name }}"
                                                    >
                                                        <svg aria-hidden="true" viewBox="0 0 24 24">
                                                            <path d="M6 6l12 12M18 6 6 18" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if($row['classes']->isEmpty())
                                        <span class="text-muted small">Belum ada kelas</span>
                                    @endif
                                    @if($canManage)
                                        <button
                                            class="btn btn-sm p-0 sibk-icon-button sibk-report-control sibk-class-add"
                                            type="button"
                                            data-bs-toggle="modal"
                                            data-bs-target="#classPickerModal"
                                            data-teacher-id="{{ $row['teacher']->id }}"
                                            data-teacher-name="{{ $row['teacher']->name }}"
                                            aria-label="Tambah kelas untuk {{ $row['teacher']->name }}"
                                        >
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <path d="M12 5v14M5 12h14" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $row['student_count'] }}</td>
                            <td>
                                <span class="sibk-badge sibk-badge--{{ $row['classes']->isNotEmpty() ? 'success' : 'neutral' }}">
                                    {{ $row['classes']->isNotEmpty() ? 'Ditugaskan' : 'Belum ditugaskan' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center py-4 text-muted" colspan="5">
                                Belum ada Guru BK yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($canManage)
            <div
                class="modal fade"
                id="classUnassignModal"
                tabindex="-1"
                aria-labelledby="classUnassignTitle"
                aria-describedby="classUnassignDescription"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="classUnassignTitle">Batalkan penugasan?</h2>
                            <button
                                class="btn-close"
                                type="button"
                                data-bs-dismiss="modal"
                                aria-label="Tutup"
                            ></button>
                        </div>
                        <div class="modal-body" id="classUnassignDescription">
                            <p class="mb-2">
                                <strong data-unassign-class></strong> akan dilepas dari
                                <strong data-unassign-teacher></strong>.
                            </p>
                            <p class="text-muted mb-0">
                                Kelas kembali belum ditugaskan. Pada tahun aktif, akses murid Guru BK
                                langsung berkurang; catatan layanan tetap tersimpan.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">
                                Kembali
                            </button>
                            <button class="btn btn-outline-danger" type="button" data-confirm-unassign>
                                Ya, batalkan penugasan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="modal fade"
                id="classPickerModal"
                tabindex="-1"
                aria-labelledby="classPickerTitle"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="classPickerTitle">Tambah kelas</h2>
                            <button
                                class="btn-close"
                                type="button"
                                data-bs-dismiss="modal"
                                aria-label="Tutup"
                            ></button>
                        </div>
                        <form
                            class="modal-body"
                            id="classPickerForm"
                            action="{{ route('assignments.classes.batch') }}"
                            method="POST"
                        >
                            @csrf
                            <input name="user_id" type="hidden">
                            <div data-class-picker-inputs></div>
                            <div class="sibk-class-picker-selected mb-3" aria-live="polite">
                                <span class="small fw-semibold d-block mb-2">Kelas dipilih</span>
                                <div class="d-flex flex-wrap gap-2" data-class-picker-selected></div>
                                <span class="small text-muted" data-class-picker-placeholder>
                                    Belum ada kelas dipilih.
                                </span>
                            </div>
                            <label class="form-label" for="classPickerSearch">Cari kelas yang belum ditugaskan</label>
                            <input
                                class="form-control mb-3"
                                id="classPickerSearch"
                                type="search"
                                autocomplete="off"
                                placeholder="Ketik nama kelas"
                            >
                            <div class="sibk-class-picker-list">
                                @forelse($unassignedClasses as $availableClass)
                                    <button
                                        class="sibk-class-picker-option"
                                        type="button"
                                        data-class-picker-option="{{ mb_strtolower($availableClass->name) }}"
                                        data-class-id="{{ $availableClass->id }}"
                                        data-class-name="{{ $availableClass->name }}"
                                    >
                                        {{ $availableClass->name }}
                                    </button>
                                @empty
                                    <p class="text-muted mb-0">Semua kelas sudah ditugaskan.</p>
                                @endforelse
                                <p class="text-muted mb-0" data-class-picker-empty hidden>
                                    Tidak ada kelas yang cocok.
                                </p>
                            </div>
                        </form>
                        <div class="modal-footer">
                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">
                                Batal
                            </button>
                            <button
                                class="btn btn-primary"
                                type="submit"
                                form="classPickerForm"
                                data-class-picker-submit
                                disabled
                            >
                                Tambah kelas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @else
            <div class="sibk-panel">
                <div class="sibk-panel__body p-4">
                    Belum ada tahun ajaran aktif untuk menampilkan penugasan.
                </div>
            </div>
        @endif
    </div>
@endsection
