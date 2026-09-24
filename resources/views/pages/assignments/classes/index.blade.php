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
                <p>Guru BK dan kelas yang diampu pada tahun ajaran yang dipilih.</p>
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
                        <label class="form-label" for="status">Status guru</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all">Semua</option>
                            <option value="assigned" @selected(request('status') === 'assigned')>Ditugaskan</option>
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
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        @if($activationReadiness !== null)
            <section class="sibk-panel mb-4" aria-labelledby="activation-readiness-title">
                <div class="sibk-panel__body p-4">
                    <h2 class="fs-5" id="activation-readiness-title">Kesiapan Aktivasi</h2>
                    @if($activationReadiness['state'] === 'active')
                        <p class="mb-0">Tahun ajaran ini sudah aktif.</p>
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
                                        <div class="badge text-bg-light border d-inline-flex align-items-center gap-1">
                                            {{ $classroom->name }}
                                            @if($canManage)
                                                <form
                                                    action="{{ route('assignments.classes.destroy', $classroom) }}"
                                                    method="POST"
                                                    data-confirm-submit
                                                    data-confirm-message="Batalkan penugasan {{ $classroom->name }} dari {{ $row['teacher']->name }}? Scope murid guru ini langsung berkurang."
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <input name="user_id" type="hidden" value="{{ $row['teacher']->id }}">
                                                    <button
                                                        class="btn btn-sm btn-outline-danger"
                                                        type="submit"
                                                        aria-label="Batalkan penugasan {{ $classroom->name }} dari {{ $row['teacher']->name }}"
                                                        style="min-width: 44px; min-height: 44px;"
                                                    >×</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if($row['classes']->isEmpty())
                                        <span class="text-muted">—</span>
                                    @endif
                                    @if($canManage)
                                        <details>
                                            <summary
                                                class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center"
                                                aria-label="Tambah kelas untuk {{ $row['teacher']->name }}"
                                                style="min-width: 44px; min-height: 44px;"
                                            >+</summary>
                                            <div class="border rounded p-2 mt-2">
                                                @forelse($unassignedClasses as $availableClass)
                                                    <form
                                                        action="{{ route('assignments.classes.store') }}"
                                                        method="POST"
                                                    >
                                                        @csrf
                                                        <input name="user_id" type="hidden" value="{{ $row['teacher']->id }}">
                                                        <input name="classroom_id" type="hidden" value="{{ $availableClass->id }}">
                                                        <input name="only_if_unassigned" type="hidden" value="1">
                                                        <button class="btn btn-link text-start" type="submit">
                                                            {{ $availableClass->name }}
                                                        </button>
                                                    </form>
                                                @empty
                                                    <span class="text-muted">Tidak ada kelas yang belum ditugaskan.</span>
                                                @endforelse
                                            </div>
                                        </details>
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
    </div>
@endsection
