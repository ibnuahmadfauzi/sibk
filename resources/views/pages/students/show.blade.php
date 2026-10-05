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
            <div class="table-responsive"><table class="table sibk-table mb-0 align-middle"><thead><tr><th scope="col">Hari/Tanggal</th><th scope="col">Jenis Masalah</th><th scope="col">Sumber</th><th scope="col">Status</th><th scope="col">Guru BK</th><th scope="col">Aksi</th></tr></thead><tbody>@forelse($cases as $case)@php $badgeTone = match($case->status?->code) { 'selesai' => 'success', 'sedang_diproses' => 'info', 'membutuhkan_tindak_lanjut' => 'warning', default => 'primary', }; @endphp<tr><td><div class="fw-semibold text-dark">{{ $case->service_date->locale('id')->translatedFormat('d M Y') }}</div><div class="text-muted small">({{ $case->service_date->locale('id')->translatedFormat('l') }})</div></td><td>{{ $case->serviceField->label }}</td><td>{{ $case->source->label }}</td><td><span class="sibk-badge sibk-badge--{{ $badgeTone }}">{{ $case->status->label }}</span></td><td>{{ $case->assignments->first()?->teacher?->name ?? '—' }}</td><td><div class="d-flex align-items-center gap-1"><a href="{{ route('cases.show', $case) }}" @unless($isWakaSummary)data-modal-url="{{ route('cases.show', [$case, 'modal' => 1]) }}"@endunless class="btn btn-icon-action btn-icon-action--info" title="Lihat selengkapnya" aria-label="Lihat selengkapnya"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v6m0-10v.01"/></svg></a></div></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat permasalahan yang dapat diakses.</td></tr>@endforelse</tbody></table></div>
            <details class="sibk-panel mt-4">
                <summary class="sibk-panel__header p-4 fw-semibold">Riwayat kelas dan aktivitas layanan</summary>
                <div class="sibk-panel__body p-4 row g-4">
                    <div class="col-12 col-lg-6"><h3 class="fs-6">Riwayat kelas</h3>@forelse($memberships as $membership)<div class="border-bottom py-2"><strong>{{ $membership->classroom->name }}</strong><span class="small text-muted d-block">{{ $membership->academicYear->name }}</span></div>@empty<p class="text-muted">Belum ada riwayat kelas.</p>@endforelse</div>
                    <div class="col-12 col-lg-6"><h3 class="fs-6">Aktivitas layanan</h3>@forelse($recentActivities as $activity)<div class="border-bottom py-2"><strong>{{ $activity['date']?->locale('id')->translatedFormat('d M Y') }}</strong><span class="text-muted d-block">{{ $activity['label'] }}</span></div>@empty<p class="text-muted">Belum ada layanan.</p>@endforelse</div>
                </div>
            </details>
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
                            <th scope="col">Kelas Saat Kejadian</th>
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
                                <td><div class="fw-semibold text-dark">{{ $record->violation_type }}</div></td>
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
            <div class="table-responsive"><table class="table sibk-table mb-0 align-middle"><thead><tr><th scope="col">Hari/Tanggal</th><th scope="col">Jenis Layanan</th><th scope="col">Permasalahan</th><th scope="col">Penanganan</th><th scope="col">Hasil</th><th scope="col">Aksi</th></tr></thead><tbody>@forelse($consultations as $session)<tr><td><div class="fw-semibold text-dark">{{ $session->session_date->locale('id')->translatedFormat('d M Y') }}</div><div class="text-muted small">({{ $session->session_date->locale('id')->translatedFormat('l') }})</div></td><td>{{ $session->serviceField->label }}</td><td>{{ \Illuminate\Support\Str::limit($session->problem, 100) }}</td><td>{{ \Illuminate\Support\Str::limit($session->handling, 100) }}</td><td>{{ \Illuminate\Support\Str::limit($session->result, 100) }}</td><td><div class="d-flex align-items-center gap-1"><a href="{{ route('consultations.show', $session) }}" data-modal-url="{{ route('consultations.show', [$session, 'modal' => 1]) }}" class="btn btn-icon-action btn-icon-action--info" title="Lihat detail" aria-label="Lihat detail konsultasi"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></a></div></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada konsultasi yang dapat diakses.</td></tr>@endforelse</tbody></table></div>
        @else
            <div class="table-responsive"><table class="table sibk-table mb-0 align-middle"><thead><tr><th scope="col">Hari/Tanggal</th><th scope="col">Kegiatan</th><th scope="col">Jenis / Tingkat</th><th scope="col">Hasil</th><th scope="col">Pencatat</th><th scope="col">Aksi</th></tr></thead><tbody>@forelse($achievements as $achievement)<tr><td><div class="fw-semibold text-dark">{{ $achievement->achievement_date->locale('id')->translatedFormat('d M Y') }}</div><div class="text-muted small">({{ $achievement->achievement_date->locale('id')->translatedFormat('l') }})</div></td><td><div class="fw-semibold text-dark">{{ $achievement->activity_name }}</div><div class="text-muted small">{{ $achievement->organizer }}</div></td><td>{{ $achievement->type->label }} / {{ $achievement->level->label }}</td><td><span class="sibk-badge sibk-badge--success">{{ $achievement->result }}</span></td><td>{{ $achievement->recorder->name }}</td><td><div class="d-flex align-items-center gap-1"><a href="{{ route('achievements.show', $achievement) }}" data-modal-url="{{ route('achievements.show', [$achievement, 'modal' => 1]) }}" class="btn btn-icon-action btn-icon-action--info" title="Lihat selengkapnya" aria-label="Lihat selengkapnya"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v6m0-10v.01"/></svg></a></div></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada prestasi yang dapat ditampilkan.</td></tr>@endforelse</tbody></table></div>
        @endif
        <div class="modal fade" id="case-modal" tabindex="-1" aria-labelledby="case-modal-title" aria-hidden="true" data-service-record-modal>
            <div class="modal-dialog {{ in_array($activeTab, ['kasus', 'konsultasi', 'prestasi'], true) ? 'modal-lg' : '' }} modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><h2 class="modal-title fs-5" id="case-modal-title">Detail Layanan BK</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body"><p class="text-muted mb-0">Memuat data…</p></div>
            </div></div>
            <div data-modal-submit-error>
                <x-notification-toast tone="error" title="Gagal menyimpan"><span data-modal-submit-error-message></span></x-notification-toast>
            </div>
        </div>
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
