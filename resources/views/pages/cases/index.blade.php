@extends('layouts.app-2')

@section('page-title', 'Layanan BK - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy"><h1>Layanan BK</h1><p>Cari, filter, dan kelola penanganan kasus serta sesi konsultasi.</p></div>
            @if($activeTab === 'konsultasi' && $canCreateConsultation)<a href="{{ route('consultations.create') }}" class="btn btn-primary">Catat Konsultasi</a>@elseif($activeTab === 'kasus' && $canCreateCase)<a href="{{ route('cases.create') }}" class="btn btn-primary">Buat Kasus Baru</a>@endif
        </div>

        <div class="sibk-panel mb-4 p-2"><ul class="nav nav-pills gap-1">
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'kasus' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'kasus']) }}">Kasus & Penanganan</a></li>
            @can('viewAny', \App\Models\Consultation::class)<li class="nav-item"><a class="nav-link {{ $activeTab === 'konsultasi' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'konsultasi']) }}">Sesi Bimbingan & Konsultasi</a></li>@endcan
        </ul></div>

        @if($activeTab === 'kasus')
            <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><form class="row g-3 align-items-end" action="{{ route('cases.index') }}" method="GET">
                <input type="hidden" name="tab" value="kasus">
                <div class="col-12 col-md-3"><label class="form-label" for="case_search">Cari kasus</label><input class="form-control" id="case_search" name="search" value="{{ request('search') }}" placeholder="Nomor kasus atau nama murid"></div>
                <div class="col-12 col-md-2"><label class="form-label" for="case_class">Kelas</label><select class="form-select" id="case_class" name="classroom_id"><option value="">Semua kelas</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
                <div class="col-12 col-md-2"><label class="form-label" for="case_source">Sumber</label><select class="form-select" id="case_source" name="case_source_id"><option value="">Semua sumber</option>@foreach($caseSources as $source)<option value="{{ $source->id }}" @selected((string) request('case_source_id') === (string) $source->id)>{{ $source->label }}</option>@endforeach</select></div>
                <div class="col-12 col-md-2"><label class="form-label" for="case_status">Status</label><select class="form-select" id="case_status" name="status_id"><option value="">Semua status</option>@foreach($caseStatuses as $status)<option value="{{ $status->id }}" @selected((string) request('status_id') === (string) $status->id)>{{ $status->label }}</option>@endforeach</select></div>
                <div class="col-12 col-md-2"><label class="form-label" for="case_month">Periode</label><input type="month" class="form-control" id="case_month" name="month" value="{{ request('month') }}"></div>
                <div class="col-12 col-md-1"><button class="btn btn-outline-primary w-100">Filter</button></div>
            </form></div></div>
            <div class="sibk-panel"><div class="sibk-panel__header p-4"><h2 class="sibk-panel__title">Daftar Kasus Aktif & Penanganan</h2><p class="sibk-panel__subtitle">{{ $cases->total() }} kasus sesuai kewenangan Anda.</p></div><div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>No. Kasus</th><th>Murid</th><th>Kelas</th><th>Sumber</th><th>Bidang</th><th>Status</th><th>Tindak Lanjut</th><th></th></tr></thead><tbody>
                @forelse($cases as $case)
                    @php $membership = $case->student?->classMemberships->sortByDesc('effective_from')->first(); $next = $case->followUps->first(fn ($item) => $item->planned_date->gte(today()) && $item->status?->code !== 'dibatalkan'); @endphp
                    <tr><td class="fw-bold text-primary">{{ $case->registration_number }}</td><td class="fw-semibold">{{ $case->identityName() }}@if($case->temporary_student_id) <span class="badge bg-warning-subtle text-warning-emphasis">Sementara</span>@endif</td><td>{{ $membership?->classroom?->name ?? '—' }}</td><td>{{ $case->source->label }}</td><td>{{ $case->serviceField->label }}</td><td><span class="sibk-badge sibk-badge--primary">{{ $case->status->label }}</span></td><td>{{ $next?->planned_date?->locale('id')->translatedFormat('d M Y') ?? '—' }}</td><td><a href="{{ route('cases.show', $case) }}" class="fw-bold text-decoration-none">Buka</a></td></tr>
                @empty<tr><td colspan="8" class="text-center text-muted py-4">Belum ada kasus yang dapat Anda akses.</td></tr>@endforelse
            </tbody></table></div>@if($cases->hasPages())<div class="p-4">{{ $cases->links() }}</div>@endif</div>
        @else
            <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><form class="row g-3 align-items-end" action="{{ route('cases.index') }}" method="GET">
                <input type="hidden" name="tab" value="konsultasi">
                <div class="col-12 col-lg-3"><label class="form-label" for="consultation_search">Cari sesi</label><input class="form-control" id="consultation_search" name="search" value="{{ request('search') }}" placeholder="Nomor, nama, NISN, atau topik"></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="consultation_class">Kelas</label><select class="form-select" id="consultation_class" name="classroom_id"><option value="">Semua kelas</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string) request('classroom_id') === (string) $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="consultation_field">Jenis</label><select class="form-select" id="consultation_field" name="service_field_id"><option value="">Semua jenis</option>@foreach($serviceFields as $field)<option value="{{ $field->id }}" @selected((string) request('service_field_id') === (string) $field->id)>{{ $field->label }}</option>@endforeach</select></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="consultation_status">Status</label><select class="form-select" id="consultation_status" name="consultation_status_id"><option value="">Semua status</option>@foreach($consultationStatuses as $status)<option value="{{ $status->id }}" @selected((string) request('consultation_status_id') === (string) $status->id)>{{ $status->label }}</option>@endforeach</select></div>
                <div class="col-6 col-lg-2"><label class="form-label" for="consultation_month">Bulan</label><input type="month" class="form-control" id="consultation_month" name="month" value="{{ request('month') }}"></div>
                <div class="col-12 col-lg-1"><button class="btn btn-outline-primary w-100">Filter</button></div>
            </form></div></div>
            <div class="sibk-panel"><div class="sibk-panel__header p-4"><h2 class="sibk-panel__title">Jadwal & Riwayat Konsultasi</h2><p class="sibk-panel__subtitle">{{ $consultations->total() }} sesi sesuai kewenangan Anda.</p></div><div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>No. Sesi</th><th>Murid & Kelas</th><th>Topik & Jenis</th><th>Jadwal</th><th>Status</th><th>Guru BK</th><th></th></tr></thead><tbody>
                @forelse($consultations as $session)
                    @php $membership = $session->student?->classMemberships->sortByDesc('effective_from')->first(); @endphp
                    <tr><td class="fw-bold text-primary">{{ $session->registration_number }}</td><td><strong>{{ $session->identityName() }}</strong><div class="small text-muted">{{ $membership?->classroom?->name ?? ($session->temporary_student_id ? 'Identitas sementara' : '—') }}</div></td><td><span class="fw-semibold">{{ $session->topic }}</span><div class="small text-muted">{{ $session->serviceField->label }}</div></td><td>{{ $session->session_date->locale('id')->translatedFormat('d M Y') }}@if($session->starts_at)<div class="small text-muted">{{ substr($session->starts_at, 0, 5) }}@if($session->ends_at)–{{ substr($session->ends_at, 0, 5) }}@endif</div>@endif</td><td><span class="sibk-badge sibk-badge--primary">{{ $session->status->label }}</span></td><td>{{ $session->counselor->name }}</td><td><a href="{{ route('consultations.show', $session) }}" class="fw-bold text-decoration-none">Buka</a></td></tr>
                @empty<tr><td colspan="7" class="text-center text-muted py-4">Belum ada sesi konsultasi yang dapat Anda akses.</td></tr>@endforelse
            </tbody></table></div>@if($consultations->hasPages())<div class="p-4">{{ $consultations->links() }}</div>@endif</div>
        @endif
    </div>
@endsection
