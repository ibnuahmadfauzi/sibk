@extends('layouts.app-2')

@section('page-title', 'Penugasan Kelas - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-401">
        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif

        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Penugasan Kelas</h1>
                <p>Guru BK penanggung jawab setiap kelas pada tahun ajaran yang dipilih.</p>
            </div>
        </div>

        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <form
                    class="row g-3 align-items-end"
                    action="{{ route('assignments.classes.index') }}"
                    method="GET"
                >
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="academic_year_id">Tahun ajaran</label>
                        <select class="form-select" id="academic_year_id" name="academic_year_id">
                            @foreach($academicYears as $year)
                                <option
                                    value="{{ $year->id }}"
                                    @selected($selectedYear?->id === $year->id)
                                >
                                    {{ $year->name }}{{ $year->is_active ? ' (Aktif)' : ($year->activated_at ? ' (Arsip)' : ' (Persiapan)') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="search_kelas">Cari kelas</label>
                        <input
                            class="form-control"
                            id="search_kelas"
                            name="search_kelas"
                            type="search"
                            value="{{ request('search_kelas') }}"
                        >
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all">Semua</option>
                            <option value="assigned" @selected(request('status') === 'assigned')>Sudah ditugaskan</option>
                            <option value="unassigned" @selected(request('status') === 'unassigned')>Belum ditugaskan</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        @if($activationReadiness !== null)
            <section class="sibk-panel mb-4" aria-labelledby="activation-readiness-title">
                <div class="sibk-panel__body p-4">
                    <h2 class="fs-5" id="activation-readiness-title">Kesiapan Aktivasi</h2>
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
                    @elseif($activationReadiness['state'] === 'scheduled')
                        <p class="mb-0">
                            Siap diaktifkan mulai {{ $selectedYear->starts_on?->locale('id')->translatedFormat('j F Y') }}.
                        </p>
                    @elseif($activationReadiness['state'] === 'ended')
                        <p class="mb-0">Periode selesai.</p>
                    @endif
                </div>
            </section>
        @endif

        <div class="table-responsive">
            <table class="table sibk-table mb-0">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Murid</th>
                        <th>Guru BK</th>
                        <th>Status</th>
                        @if($canManage)
                            <th>Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $classroom)
                        @php($assignment = $classroom->teacherAssignments->first())
                        <tr>
                            <td class="fw-bold">{{ $classroom->name }}</td>
                            <td>{{ $classroom->student_count }}</td>
                            <td>{{ $assignment?->teacher?->name ?? '—' }}</td>
                            <td>
                                <span class="sibk-badge sibk-badge--{{ $assignment ? 'success' : 'neutral' }}">
                                    {{ $assignment ? 'Ditugaskan' : 'Belum ditugaskan' }}
                                </span>
                            </td>
                            @if($canManage)
                                <td>
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#classAssignmentModal"
                                        data-classroom-id="{{ $classroom->id }}"
                                        data-classroom-name="{{ $classroom->name }}"
                                        data-user-id="{{ $assignment?->user_id }}"
                                    >
                                        Atur
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td
                                class="text-center py-4 text-muted"
                                colspan="{{ $canManage ? 5 : 4 }}"
                            >
                                Belum ada kelas yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($canManage)
            <div
                class="modal fade"
                id="classAssignmentModal"
                tabindex="-1"
                aria-labelledby="classAssignmentTitle"
                aria-hidden="true"
            >
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('assignments.classes.store') }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="classAssignmentTitle">Atur penugasan kelas</h2>
                                <button
                                    class="btn-close"
                                    type="button"
                                    data-bs-dismiss="modal"
                                    aria-label="Tutup"
                                ></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3" data-assignment-class-name></p>
                                <input name="classroom_id" type="hidden" required>
                                <label class="form-label" for="assignment_user_id">Guru BK</label>
                                <select
                                    class="form-select"
                                    id="assignment_user_id"
                                    name="user_id"
                                    required
                                >
                                    <option value="">Pilih Guru BK</option>
                                    @foreach($counselors as $counselor)
                                        <option value="{{ $counselor->id }}">{{ $counselor->name }}</option>
                                    @endforeach
                                </select>
                                @if($selectedYear !== null && ! $selectedYear->is_active)
                                    <p class="form-text">Penugasan tahun Persiapan belum memberi akses murid.</p>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
                                <button class="btn btn-primary" type="submit">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
