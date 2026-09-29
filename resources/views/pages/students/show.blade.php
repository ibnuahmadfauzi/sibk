@extends('layouts.app-2')

@section('page-title', 'Profil Murid - Ruang BK')

@section('body')
    @php
        $initials = collect(explode(' ', $student->name))->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->join('');
    @endphp
    <div class="sibk-dashboard" data-page-id="PG-202">
        <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy"><a href="{{ route('students.index') }}" class="text-decoration-none small">&larr; Daftar Murid</a><h1>Profil Murid</h1><p>Riwayat layanan dan informasi terkait murid.</p></div>
        </div>

        @if($isWakaSummary)<div class="alert alert-info">Profil ini dibatasi pada ringkasan dan permasalahan yang dikoordinasikan kepada Anda.</div>@endif
        <section class="sibk-student-hero mb-4" aria-label="Identitas dan ringkasan murid">
            <div class="sibk-student-hero__identity">
                <div class="sibk-student-avatar" aria-hidden="true">{{ $initials }}</div>
                <div class="sibk-student-hero__identity-text">
                    <h2>{{ $student->name }}@if($departure && $departure->status === \App\Models\StudentDeparture::STATUS_OFFICIAL) <span class="badge text-bg-light ms-2 fs-6 align-middle">{{ $departure->statusLabel() }}</span>@endif</h2>
                    <div>NISN {{ $student->nisn }}</div>
                    <div>{{ $currentMembership?->classroom?->name ?? 'Tanpa kelas aktif' }}</div>
                    <div>{{ $currentMembership?->academicYear?->name ?? 'Tahun ajaran tidak tersedia' }}</div>
                </div>
            </div>
            <div class="sibk-student-hero__stats">
                <div class="sibk-student-hero__stat sibk-tone--primary"><h3>Layanan BK</h3><div class="sibk-student-hero__service"><strong>{{ $stats['cases'] }}</strong><span>Permasalahan</span></div><div class="sibk-student-hero__service"><strong>{{ $stats['consultations'] }}</strong><span>Konsultasi</span></div></div>
                <div class="sibk-student-hero__stat sibk-tone--danger"><h3>Poin e-Tatib</h3><strong class="sibk-student-hero__number sibk-student-hero__number--danger">{{ $stats['points'] }}</strong><small>Terakhir disinkronkan: {{ $stats['last_synced_at']?->locale('id')->translatedFormat('d M Y') ?? 'Belum tersedia' }}</small></div>
                <div class="sibk-student-hero__stat sibk-tone--success"><h3>Prestasi</h3><strong class="sibk-student-hero__number sibk-student-hero__number--success">{{ $stats['achievements'] }}</strong><small>Tercatat</small></div>
            </div>
        </section>

        <ul class="nav nav-pills mb-4 gap-2" aria-label="Riwayat murid">
            @foreach(['kasus' => 'Permasalahan', 'etatib' => 'Data e-Tatib'] as $key => $label)<li class="nav-item"><a class="nav-link {{ $activeTab === $key ? 'active' : '' }}" href="{{ route('students.show', ['student' => $student, 'tab' => $key]) }}" @if($activeTab === $key) aria-current="page" @endif>{{ $label }}</a></li>@endforeach
            @if($canViewConsultations)<li class="nav-item"><a class="nav-link {{ $activeTab === 'konsultasi' ? 'active' : '' }}" href="{{ route('students.show', ['student' => $student, 'tab' => 'konsultasi']) }}">Konsultasi</a></li>@endif
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'prestasi' ? 'active' : '' }}" href="{{ route('students.show', ['student' => $student, 'tab' => 'prestasi']) }}">Prestasi</a></li>
        </ul>

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
            <details class="sibk-panel mt-4">
                <summary class="sibk-panel__header p-4 fw-semibold">Riwayat kelas dan aktivitas layanan</summary>
                <div class="sibk-panel__body p-4 row g-4">
                    <div class="col-12 col-lg-6"><h3 class="fs-6">Riwayat kelas</h3>@forelse($memberships as $membership)<div class="border-bottom py-2"><strong>{{ $membership->classroom->name }}</strong><span class="small text-muted d-block">{{ $membership->academicYear->name }}</span></div>@empty<p class="text-muted">Belum ada riwayat kelas.</p>@endforelse</div>
                    <div class="col-12 col-lg-6"><h3 class="fs-6">Aktivitas layanan</h3>@forelse($recentActivities as $activity)<div class="border-bottom py-2"><strong>{{ $activity['date']?->locale('id')->translatedFormat('d M Y') }}</strong><span class="text-muted d-block">{{ $activity['label'] }}</span></div>@empty<p class="text-muted">Belum ada layanan.</p>@endforelse</div>
                </div>
            </details>
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
                                <td>{{ $record->occurred_at?->locale('id')->translatedFormat('d M Y H:i') ?? 'Tanggal belum tersedia' }}</td>
                                <td>{{ $record->violation_type }}</td>
                                <td>{{ $record->category }}</td>
                                <td class="fw-bold text-danger">+{{ $record->points }}</td>
                                <td>{{ $record->source_total_points ?? '-' }}</td>
                                <td>{{ $record->effective_classroom_name ?: '-' }}</td>
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
            <div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>Tanggal</th><th>Kegiatan</th><th>Jenis / Tingkat</th><th>Hasil</th><th>Pencatat</th><th></th></tr></thead><tbody>@forelse($achievements as $achievement)<tr><td>{{ $achievement->achievement_date->locale('id')->translatedFormat('d M Y') }}</td><td>{{ $achievement->activity_name }}<span class="small text-muted d-block">{{ $achievement->organizer }}</span></td><td>{{ $achievement->type->label }} / {{ $achievement->level->label }}</td><td>{{ $achievement->result }}</td><td>{{ $achievement->recorder->name }}</td><td><a href="{{ route('achievements.show', $achievement) }}">Buka</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-5">Belum ada prestasi yang dapat ditampilkan.</td></tr>@endforelse</tbody></table></div>
        @endif
    </div>
@endsection
