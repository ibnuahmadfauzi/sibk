@extends('layouts.app-2')

@section('page-title', 'Layanan BK - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-101">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy"><h1>Layanan BK</h1><p>Cari, filter, dan kelola penanganan kasus serta sesi konsultasi.</p></div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a class="nav-link {{ $activeTab === 'kasus' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'kasus']) }}">Kasus & Penanganan</a></li>
                @can('viewAny', \App\Models\Consultation::class)<li class="nav-item"><a class="nav-link {{ $activeTab === 'konsultasi' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'konsultasi']) }}">Sesi Bimbingan & Konsultasi</a></li>@endcan
            </ul>
            @if($activeTab === 'konsultasi' && $canCreateConsultation)<a href="{{ route('consultations.create') }}" class="btn btn-primary">Catat Konsultasi</a>@elseif($activeTab === 'kasus' && $canCreateCase)<a href="{{ route('cases.create') }}" class="btn btn-primary">Buat Kasus Baru</a>@endif
        </div>

        @if($activeTab === 'kasus')
            <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><form class="sibk-filter-form row g-3 align-items-end" action="{{ route('cases.index') }}" method="GET">
                <input type="hidden" name="tab" value="kasus">
                <div class="col-12 col-md-6"><label class="form-label" for="case_search">Cari kasus</label><input class="form-control" id="case_search" name="search" value="{{ request('search') }}" placeholder="Nama murid"></div>
                <div class="col-12 col-md-4"><label class="form-label" for="case_status">Status</label><select class="form-select" id="case_status" name="status_id"><option value="">Semua status</option>@foreach($caseStatuses as $status)<option value="{{ $status->id }}" @selected((string) request('status_id') === (string) $status->id)>{{ $status->label }}</option>@endforeach</select></div>
                <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
            </form></div></div>
            @php
                $sortUrl = fn (string $column) => route('cases.index', array_merge(request()->query(), [
                    'sort' => $column,
                    'direction' => request('sort') === $column && request('direction') === 'asc' ? 'desc' : 'asc',
                ]));
            @endphp
            <div class="table-responsive"><table class="table sibk-table mb-0 align-middle"><thead><tr>
                <th><a href="{{ $sortUrl('nama') }}">Murid</a></th>
                <th><a href="{{ $sortUrl('kelas') }}">Kelas</a></th>
                <th><a href="{{ $sortUrl('tanggal') }}">Tanggal</a></th>
                <th><a href="{{ $sortUrl('sumber') }}">Sumber</a></th>
                <th><a href="{{ $sortUrl('bidang') }}">Bidang</a></th>
                <th><a href="{{ $sortUrl('status') }}">Status</a></th>
                <th>Tindak Lanjut</th>
                <th>Aksi</th>
            </tr></thead><tbody>
                @forelse($cases as $case)
                    @php
                        $membership = $case->student?->classMemberships
                            ->sortByDesc('effective_from')
                            ->first(fn ($item) => $item->effective_from->lte($case->service_date)
                                && ($item->effective_until === null || $item->effective_until->gte($case->service_date)));
                        $completed = $case->status?->code === \App\Support\ServiceRecordStatus::COMPLETED;
                        $badgeTone = match($case->status?->code) {
                            'selesai' => 'success',
                            'sedang_diproses', 'membutuhkan_tindak_lanjut' => 'warning',
                            default => 'primary',
                        };
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $case->identityName() }}@if($case->temporary_student_id) <span class="badge bg-warning-subtle text-warning-emphasis">Sementara</span>@endif</td>
                        <td>{{ $membership?->classroom?->name ?? '—' }}</td>
                        <td>{{ $case->service_date->locale('id')->translatedFormat('d M Y') }}</td>
                        <td>{{ $case->source->label }}</td>
                        <td>{{ $case->serviceField->label }}</td>
                        <td><span id="case-status-{{ $case->id }}" class="sibk-badge sibk-badge--{{ $badgeTone }}">{{ $case->status->label }}</span></td>
                        <td>
                            @can('update', $case)
                                <select class="form-select form-select-sm" aria-label="Jenis tindak lanjut {{ $case->identityName() }}"
                                    @disabled($completed)
                                    data-follow-up-url="{{ route('cases.follow-up.update', $case) }}"
                                    data-follow-up-status-target="#case-status-{{ $case->id }}"
                                    data-follow-up-timestamp-target="#case-updated-at-{{ $case->id }}"
                                    data-expected-updated-at="{{ $case->updated_at->toJSON() }}"
                                    data-previous-value="{{ $case->follow_up_type_id }}">
                                    <option value="">Tidak ada</option>
                                    @foreach($followUpTypes as $type)<option value="{{ $type->id }}" @selected($case->follow_up_type_id === $type->id)>{{ $type->label }}</option>@endforeach
                                </select>
                                <span class="small text-muted" data-save-status aria-live="polite"></span>
                                <span id="case-updated-at-{{ $case->id }}" class="visually-hidden">{{ $case->updated_at->toJSON() }}</span>
                            @else
                                <span class="text-muted">{{ $case->followUpType?->label ?? '—' }}</span>
                            @endcan
                        </td>
                        <td><div class="d-flex flex-wrap gap-1">
                            <a href="{{ route('cases.show', $case) }}" data-modal-url="{{ route('cases.show', [$case, 'modal' => 1]) }}" class="btn btn-sm btn-outline-info">Detail</a>
                            @can('update', $case)
                                <a href="{{ route('cases.edit', $case) }}" data-modal-url="{{ route('cases.edit', [$case, 'modal' => 1]) }}"
                                    @if($completed) data-confirm-message="Kasus ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?" onclick="if (! window.confirm('Kasus ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?')) { event.stopImmediatePropagation(); return false; }" @endif
                                    class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                            @can('archive', $case)<form action="{{ route('cases.destroy', $case) }}" method="POST" data-confirm-submit data-confirm-message="Data akan diarsipkan dan tidak tampil pada daftar utama. Lanjutkan?">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button></form>@endcan
                        </div></td>
                    </tr>
                @empty<tr><td colspan="8" class="text-center text-muted py-4">Belum ada kasus yang dapat Anda akses.</td></tr>@endforelse
            </tbody></table></div>@if($cases->hasPages())<div class="mt-3">{{ $cases->links() }}</div>@endif

            <div class="modal fade" id="case-modal" tabindex="-1" aria-labelledby="case-modal-title" aria-hidden="true" data-service-record-modal>
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                    <div class="modal-header"><h2 class="modal-title fs-5" id="case-modal-title">Detail Kasus</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body"><p class="text-muted mb-0">Memuat data…</p></div>
                </div></div>
            </div>
        @else
            <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><form class="sibk-filter-form row g-3 align-items-end" action="{{ route('cases.index') }}" method="GET">
                <input type="hidden" name="tab" value="konsultasi">
                <div class="col-12 col-lg-3"><label class="form-label" for="consultation_search">Cari sesi</label><input class="form-control" id="consultation_search" name="search" value="{{ request('search') }}" placeholder="Nomor, nama, NISN, atau topik"></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="consultation_class">Kelas</label><select class="form-select" id="consultation_class" name="classroom_id"><option value="">Semua kelas</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="consultation_field">Jenis</label><select class="form-select" id="consultation_field" name="service_field_id"><option value="">Semua jenis</option>@foreach($serviceFields as $field)<option value="{{ $field->id }}" @selected((string) request('service_field_id') === (string) $field->id)>{{ $field->label }}</option>@endforeach</select></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="consultation_status">Status</label><select class="form-select" id="consultation_status" name="consultation_status_id"><option value="">Semua status</option>@foreach($consultationStatuses as $status)<option value="{{ $status->id }}" @selected((string) request('consultation_status_id') === (string) $status->id)>{{ $status->label }}</option>@endforeach</select></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="consultation_month">Bulan</label><input type="month" class="form-control" id="consultation_month" name="month" value="{{ request('month') }}"></div>
                <div class="col-12 col-lg-1"><button class="btn btn-outline-primary w-100">Filter</button></div>
            </form></div></div>
            <div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>No. Sesi</th><th>Murid & Kelas</th><th>Topik & Jenis</th><th>Jadwal</th><th>Status</th><th>Guru BK</th><th></th></tr></thead><tbody>
                @forelse($consultations as $session)
                    @php $membership = $session->student?->classMemberships->sortByDesc('effective_from')->first(); @endphp
                    <tr><td class="fw-bold text-primary">{{ $session->registration_number }}</td><td><strong>{{ $session->identityName() }}</strong><div class="small text-muted">{{ $membership?->classroom?->name ?? ($session->temporary_student_id ? 'Identitas sementara' : '—') }}</div></td><td><span class="fw-semibold">{{ $session->topic }}</span><div class="small text-muted">{{ $session->serviceField->label }}</div></td><td>{{ $session->session_date->locale('id')->translatedFormat('d M Y') }}@if($session->starts_at)<div class="small text-muted">{{ substr($session->starts_at, 0, 5) }}@if($session->ends_at)–{{ substr($session->ends_at, 0, 5) }}@endif</div>@endif</td><td><span class="sibk-badge sibk-badge--primary">{{ $session->status->label }}</span></td><td>{{ $session->counselor->name }}</td><td><a href="{{ route('consultations.show', $session) }}" class="fw-bold text-decoration-none">Buka</a></td></tr>
                @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada sesi konsultasi yang dapat Anda akses.</td></tr>@endforelse
            </tbody></table></div>@if($consultations->hasPages())<div class="mt-3">{{ $consultations->links() }}</div>@endif
        @endif
    </div>
@endsection
