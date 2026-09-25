@extends('layouts.app-2')

@section('page-title', 'Profil Murid - Ruang BK')

@section('body')
    @php
        $initials = collect(explode(' ', $student->name))->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->join('');
    @endphp
    <div class="sibk-dashboard" data-page-id="PG-202">
        <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy"><a href="{{ route('students.index') }}" class="text-decoration-none small">&larr; Daftar Murid</a><h1>Profil Murid</h1><p>Riwayat layanan dan informasi terkait murid.</p></div>
            <div class="d-flex flex-wrap align-items-start gap-2">
                @if($canCreateConsultation)<a href="{{ route('consultations.create', ['student_id' => $student->id]) }}" class="btn btn-outline-primary">Catat Konsultasi</a>@endif
                @if($canCreateAchievement)<a href="{{ route('achievements.create', ['student_id' => $student->id]) }}" class="btn btn-outline-primary">Catat Prestasi</a>@endif
                @if($canCreateCase)<a href="{{ route('cases.create', ['student_id' => $student->id]) }}" class="btn btn-primary">Catat Permasalahan</a>@endif
            </div>
        </div>

        @if($isWakaSummary)<div class="alert alert-info">Profil ini dibatasi pada ringkasan dan permasalahan yang dikoordinasikan kepada Anda.</div>@endif
        <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4 d-flex align-items-center gap-3"><div class="sibk-student-avatar">{{ $initials }}</div><div><h2 class="fs-4 mb-1">{{ $student->name }}</h2><div class="text-muted small">NISN {{ $student->nisn }} &bull; {{ $currentMembership?->classroom?->name ?? 'Tanpa kelas aktif' }} &bull; {{ $currentMembership?->academicYear?->name ?? 'Tahun ajaran tidak tersedia' }}</div></div></div></div>

        @include('pages.students._departure-process')

        <ul class="nav nav-pills mb-4 gap-2">
            @foreach(['ringkasan' => 'Ringkasan', 'kasus' => 'Permasalahan dan Layanan', 'etatib' => 'Data e-Tatib'] as $key => $label)<li class="nav-item"><a class="nav-link {{ $activeTab === $key ? 'active' : '' }}" href="{{ route('students.show', ['student' => $student, 'tab' => $key]) }}">{{ $label }}</a></li>@endforeach
            @if($canViewConsultations)<li class="nav-item"><a class="nav-link {{ $activeTab === 'konsultasi' ? 'active' : '' }}" href="{{ route('students.show', ['student' => $student, 'tab' => 'konsultasi']) }}">Konsultasi</a></li>@endif
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'prestasi' ? 'active' : '' }}" href="{{ route('students.show', ['student' => $student, 'tab' => 'prestasi']) }}">Prestasi</a></li>
        </ul>

        <div class="row g-3 mb-4">
            @foreach([
                ['Permasalahan Aktif', $stats['active_cases'], 'primary', 'Dalam kewenangan'],
                ['Poin e-Tatib', $stats['points'], 'warning', 'Data yang diizinkan'],
                ['Prestasi', $stats['achievements'], 'secondary', 'Sesuai kewenangan'],
            ] as [$label, $value, $tone, $sub])
                <div class="col-6 col-xl-3"><div class="sibk-stat-card p-2 p-sm-3 border bg-white h-100"><div class="text-muted small text-truncate">{{ $label }}</div><div class="sibk-student-stat-value fw-bold text-{{ $tone }} my-1">{{ $value }}</div><div class="sibk-student-stat-sub text-muted">{{ $sub }}</div></div></div>
            @endforeach
        </div>

        @if($activeTab === 'ringkasan')
            <div class="row g-4">
                <div class="col-12 col-lg-7"><div class="sibk-panel h-100"><div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Ringkasan Operasional</h2></div><div class="sibk-panel__body p-4">
                    <p><span class="text-muted small d-block">Permasalahan terakhir</span>@if($cases->first())<a href="{{ route('cases.show', $cases->first()) }}" class="fw-semibold">Layanan permasalahan &bull; {{ $cases->first()->status->label }}</a>@else Belum ada @endif</p>
                    <div><span class="text-muted small d-block mb-2">Histori kelas</span>@forelse($memberships as $membership)<div class="border-bottom py-2"><strong>{{ $membership->classroom->name }}</strong><span class="small text-muted d-block">{{ $membership->academicYear->name }}</span></div>@empty Belum ada histori kelas. @endforelse</div>
                </div></div></div>
                <div class="col-12 col-lg-5"><div class="sibk-panel h-100"><div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Ringkasan layanan</h2></div><div class="sibk-panel__body p-4">@forelse($recentActivities as $activity)<div class="border-bottom py-2"><strong>{{ $activity['date']?->locale('id')->translatedFormat('d M Y') }}</strong><span class="text-muted d-block">{{ $activity['label'] }}</span></div>@empty<p class="text-muted mb-0">Belum ada layanan.</p>@endforelse</div></div></div>
            </div>
        @elseif($activeTab === 'kasus')
            <div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>Tanggal</th><th>Jenis Masalah</th><th>Sumber</th><th>Status</th><th>Guru BK</th><th></th></tr></thead><tbody>@forelse($cases as $case)<tr><td>{{ $case->service_date->locale('id')->translatedFormat('d M Y') }}</td><td>{{ $case->serviceField->label }}</td><td>{{ $case->source->label }}</td><td>{{ $case->status->label }}</td><td>{{ $case->assignments->first()?->teacher?->name ?? '—' }}</td><td><a href="{{ route('cases.show', $case) }}">Buka</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat permasalahan yang dapat diakses.</td></tr>@endforelse</tbody></table></div>
        @elseif($activeTab === 'etatib')
            <div class="table-responsive">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pelanggaran</th>
                            <th>Kategori</th>
                            <th>Poin</th>
                            <th>Total Resmi</th>
                            <th>Kelas Saat Kejadian</th>
                            <th>Pencatat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($etatibRecords as $record)
                            <tr>
                                <td>{{ $record->occurred_at->locale('id')->translatedFormat('d M Y H:i') }}</td>
                                <td>{{ $record->violation_type }}</td>
                                <td>{{ $record->category }}</td>
                                <td class="fw-bold text-danger">+{{ $record->points }}</td>
                                <td>{{ $record->source_total_points ?? '-' }}</td>
                                <td>{{ $record->source_classroom_name ?: '-' }}</td>
                                <td>{{ $record->recorded_by_name ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Tidak ada data e-Tatib yang dapat ditampilkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($activeTab === 'konsultasi' && $canViewConsultations)
            <div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>Tanggal</th><th>Jenis</th><th>Permasalahan</th><th>Penanganan</th><th>Hasil</th><th></th></tr></thead><tbody>@forelse($consultations as $session)<tr><td>{{ $session->session_date->locale('id')->translatedFormat('d M Y') }}</td><td>{{ $session->serviceField->label }}</td><td>{{ \Illuminate\Support\Str::limit($session->problem, 100) }}</td><td>{{ \Illuminate\Support\Str::limit($session->handling, 100) }}</td><td>{{ \Illuminate\Support\Str::limit($session->result, 100) }}</td><td><a href="{{ route('consultations.show', $session) }}">Buka</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada konsultasi yang dapat diakses.</td></tr>@endforelse</tbody></table></div>
        @else
            @if($canCreateAchievement)<div class="d-flex justify-content-end mb-3"><a href="{{ route('achievements.create', ['student_id' => $student->id]) }}" class="btn btn-primary">Catat Prestasi</a></div>@endif
            <div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Jenis / Tingkat</th><th>Hasil</th><th>Status</th><th>Pencatat</th><th></th></tr></thead><tbody>@forelse($achievements as $achievement)@php $tone = match($achievement->verificationStatus->code) {'terverifikasi' => 'success', 'ditolak' => 'danger', default => 'warning'}; @endphp<tr><td>{{ $achievement->achievement_date->locale('id')->translatedFormat('d M Y') }}</td><td>{{ $achievement->activity_name }}<span class="small text-muted d-block">{{ $achievement->organizer }}</span></td><td>{{ $achievement->type->label }} / {{ $achievement->level->label }}</td><td>{{ $achievement->result }}</td><td><span class="badge text-bg-{{ $tone }}">{{ $achievement->verificationStatus->label }}</span></td><td>{{ $achievement->recorder->name }}</td><td><a href="{{ route('achievements.show', $achievement) }}">Buka</a></td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-5">Belum ada prestasi yang dapat ditampilkan.</td></tr>@endforelse</tbody></table></div>
        @endif
    </div>
@endsection
