@extends('layouts.app-2')

@section('page-title', 'Profil Murid - Ruang BK')

@section('body')
    @php
        $initials = collect(explode(' ', $student->name))->filter()->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->join('');
    @endphp
    <div class="sibk-dashboard" data-page-id="PG-202">
        <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-start gap-3">
                <a href="{{ route('students.index') }}" class="btn btn-icon btn-light text-primary flex-shrink-0" aria-label="Kembali ke daftar murid" title="Kembali ke daftar murid">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg>
                </a>
                <div class="sibk-page-header__copy m-0"><h1>Profil Murid</h1><p>Riwayat layanan dan informasi terkait murid.</p></div>
            </div>
        </div>

        @if($isWakaSummary)<div class="alert alert-info">Profil ini dibatasi pada ringkasan dan permasalahan yang dikoordinasikan kepada Anda.</div>@endif
        <section class="sibk-student-hero mb-4" aria-label="Identitas dan ringkasan murid">
            <div class="sibk-student-hero__identity">
                <div class="sibk-student-avatar" aria-hidden="true">{{ $initials }}</div>
                <div class="sibk-student-hero__identity-text">
                    <h2>{{ \App\Support\StudentName::display($student->name) }}@if($departure && $departure->status === \App\Models\StudentDeparture::STATUS_OFFICIAL) <span class="badge text-bg-light ms-2 fs-6 align-middle">{{ $departure->statusLabel() }}</span>@endif</h2>
                    <dl class="sibk-student-hero__metadata">
                        <div><dt>NISN</dt><dd>{{ $student->nisn }}</dd></div>
                        <div><dt>Kelas</dt><dd>{{ $currentMembership?->classroom?->name ?? 'Tanpa kelas aktif' }}</dd></div>
                        <div><dt>Tahun ajaran</dt><dd>{{ $currentMembership?->academicYear?->name ?? 'Tidak tersedia' }}</dd></div>
                    </dl>
                </div>
            </div>
            <div class="sibk-student-hero__stats">
                <div class="sibk-student-hero__stat sibk-tone--primary"><h3>Layanan BK</h3><div class="sibk-student-hero__service"><strong>{{ $stats['cases'] }}</strong><span>Permasalahan</span></div><div class="sibk-student-hero__service"><strong>{{ $stats['consultations'] }}</strong><span>Konsultasi</span></div></div>
                <div class="sibk-student-hero__stat sibk-tone--danger"><h3>Akumulasi Poin di BK</h3><strong class="sibk-student-hero__number sibk-student-hero__number--danger">{{ $stats['points'] }}</strong>@if($stats['source_points'] !== null)<small>Total terakhir dari e-Tatib: {{ $stats['source_points'] }}</small>@endif<small>Terakhir disinkronkan: {{ $stats['last_synced_at']?->locale('id')->translatedFormat('d M Y') ?? 'Belum tersedia' }}</small></div>
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
            <div class="table-responsive">
                <table class="table sibk-table mb-0 align-middle">
                    <thead><tr><th scope="col">Hari/Tanggal</th><th scope="col">Jenis Masalah</th><th scope="col">Riwayat Kelas</th><th scope="col">Guru BK</th><th scope="col">Hasil/Ringkasan</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($cases as $case)
                            <tr>
                                <td><div class="fw-semibold text-dark">{{ $case->service_date->locale('id')->translatedFormat('d M Y') }}</div><div class="text-muted small">({{ $case->service_date->locale('id')->translatedFormat('l') }})</div></td>
                                <td>{{ $case->serviceField?->label ?? '—' }}</td>
                                <td>{{ $case->classroom?->name ?? '—' }}</td>
                                <td>{{ $case->assignments->first()?->teacher?->name ?? '—' }}</td>
                                <td><div>{{ $case->resolution_summary ?: '—' }}</div>@if($case->followUps->first()?->followUpType || $case->followUpType)<small class="text-muted">Tindak lanjut terakhir: {{ $case->followUps->first()?->followUpType?->label ?? $case->followUpType?->label }}</small>@endif</td>
                                <td>
                                    <button type="button" class="btn btn-icon-action btn-icon-action--info" data-report-detail-toggle data-report-detail-name="{{ \App\Support\StudentName::display($student->name) }}" aria-controls="profile-case-notes-{{ $case->id }}" aria-expanded="false" aria-label="Tampilkan detail layanan {{ \App\Support\StudentName::display($student->name) }}" title="Tampilkan detail layanan">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr class="sibk-report-detail-row d-none" id="profile-case-notes-{{ $case->id }}">
                                <td colspan="6"><div class="sibk-report-detail-panel"><div class="row g-3">
                                    <div class="col-12 col-lg-2"><strong class="d-block mb-1">Sumber</strong><p class="mb-0">{{ $case->source?->label ?? '—' }}</p></div>
                                    <div class="col-12 col-lg-5"><strong class="d-block mb-1">Latar Belakang Masalah</strong><p class="mb-0" style="white-space: pre-line">{{ $case->initial_info ?: '—' }}</p></div>
                                    <div class="col-12 col-lg-5"><strong class="d-block mb-1">Penanganan</strong><p class="mb-0" style="white-space: pre-line">{{ $case->initial_action ?: '—' }}</p></div>
                                </div></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat permasalahan yang dapat diakses.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($activeTab === 'etatib')
            <div class="table-responsive">
                <table class="table sibk-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Waktu/Tanggal</th>
                            <th scope="col">Pelanggaran</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Poin</th>
                            <!-- <th scope="col">Total Resmi</th> -->
                            <th scope="col">Riwayat Kelas</th>
                            <th scope="col">Pencatat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($etatibRecords as $record)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $record->occurred_at?->locale('id')->translatedFormat('d M Y') ?? 'Tanggal belum tersedia' }}</div>
                                    @if($record->occurred_at)
                                        <div class="text-muted small">{{ $record->occurred_at->format('H:i') }} ({{ $record->occurred_at->locale('id')->translatedFormat('l') }})</div>
                                    @endif
                                </td>
                                <td><div class="fw-semibold text-dark">{{ $record->violation_type }}</div>@if($record->source_deleted_at)<small class="text-muted">Dibatalkan sumber (tidak dihitung)</small>@elseif(!$record->is_active)<small class="text-muted">Arsip</small>@endif</td>
                                <td>{{ $record->category }}</td>
                                <td><span class="sibk-badge sibk-badge--danger">+{{ $record->points }}</span></td>
                                <!-- <td>{{ $record->source_total_points ?? '—' }}</td> -->
                                <td>{{ $record->effective_classroom_name ?: '—' }}</td>
                                <td>{{ $record->recorded_by_name ?: '—' }}</td>
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
            <div class="table-responsive">
                <table class="table sibk-table mb-0 align-middle">
                    <thead><tr><th scope="col">Hari/Tanggal</th><th scope="col">Jenis Masalah</th><th scope="col">Riwayat Kelas</th><th scope="col">Guru BK</th><th scope="col">Hasil/Ringkasan</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($consultations as $session)
                            <tr>
                                <td><div class="fw-semibold text-dark">{{ $session->session_date->locale('id')->translatedFormat('d M Y') }}</div><div class="text-muted small">({{ $session->session_date->locale('id')->translatedFormat('l') }})</div></td>
                                <td>{{ $session->serviceField?->label ?? '—' }}</td>
                                <td>{{ $session->classroom?->name ?? '—' }}</td>
                                <td>{{ $session->counselor?->name ?? '—' }}</td>
                                <td>{{ $session->result ?: '—' }}</td>
                                <td><button type="button" class="btn btn-icon-action btn-icon-action--info" data-report-detail-toggle data-report-detail-name="{{ \App\Support\StudentName::display($student->name) }}" aria-controls="profile-consultation-notes-{{ $session->id }}" aria-expanded="false" aria-label="Tampilkan detail layanan {{ \App\Support\StudentName::display($student->name) }}" title="Tampilkan detail layanan"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg></button></td>
                            </tr>
                            <tr class="sibk-report-detail-row d-none" id="profile-consultation-notes-{{ $session->id }}">
                                <td colspan="6"><div class="sibk-report-detail-panel"><div class="row g-3">
                                    <div class="col-12 col-lg-6"><strong class="d-block mb-1">Permasalahan</strong><p class="mb-0" style="white-space: pre-line">{{ $session->problem ?: '—' }}</p></div>
                                    <div class="col-12 col-lg-6"><strong class="d-block mb-1">Penanganan</strong><p class="mb-0" style="white-space: pre-line">{{ $session->handling ?: '—' }}</p></div>
                                </div></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada konsultasi yang dapat diakses.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="table-responsive"><table class="table sibk-table mb-0 align-middle"><thead><tr><th scope="col">Hari/Tanggal</th><th scope="col">Kegiatan</th><th scope="col">Jenis / Tingkat</th><th scope="col">Hasil</th></tr></thead><tbody>@forelse($achievements as $achievement)<tr><td><div class="fw-semibold text-dark">{{ $achievement->achievement_date->locale('id')->translatedFormat('d M Y') }}</div><div class="text-muted small">({{ $achievement->achievement_date->locale('id')->translatedFormat('l') }})</div></td><td><div class="fw-semibold text-dark">{{ $achievement->activity_name }}</div><div class="text-muted small">{{ $achievement->organizer }}</div></td><td>{{ $achievement->type->label }} / {{ $achievement->level->label }}</td><td><span class="sibk-badge sibk-badge--success">{{ $achievement->result }}</span></td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada prestasi yang dapat ditampilkan.</td></tr>@endforelse</tbody></table></div>
        @endif
    </div>
@endsection

@section('extra-css')
<style>
    .btn-icon-action {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 32px !important;
        height: 32px !important;
        padding: 0 !important;
        border-radius: 8px !important;
        border: 1px solid transparent !important;
        transition: all 0.15s ease !important;
        cursor: pointer !important;
        background: transparent !important;
        flex-shrink: 0 !important;
    }

    .btn-icon-action--info {
        color: #0891b2 !important;
        border-color: #cffafe !important;
        background: #f0fdfe !important;
    }
    .btn-icon-action--info:hover {
        background: #cffafe !important;
        border-color: #a5f3fc !important;
    }
</style>
@endsection
