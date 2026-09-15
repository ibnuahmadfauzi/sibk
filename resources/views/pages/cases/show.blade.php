@extends('layouts.app-2')

@section('page-title', 'Detail Kasus - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-103">
        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif
        <div class="sibk-page-header mb-4 d-flex flex-wrap justify-content-between gap-3">
            <div class="sibk-page-header__copy">
                <a href="{{ route('cases.index') }}" class="text-decoration-none small">&larr; Kembali ke daftar</a>
                <h1 class="mb-1">Detail Kasus</h1>
                <p class="mb-0">{{ $case->identityName() }} &bull; {{ $case->service_date->locale('id')->translatedFormat('d F Y') }}</p>
            </div>
            @if($canUpdateCase || $canArchiveCase || $canAssignCase)
                <div class="d-flex flex-wrap align-items-start gap-2">
                    @if($canUpdateCase)<a href="{{ route('cases.edit', $case) }}" class="btn btn-primary">Ubah Kasus</a>@endif
                    @if($canArchiveCase)<form action="{{ route('cases.destroy', $case) }}" method="POST" data-confirm-submit data-confirm-message="Data akan diarsipkan dan tidak tampil pada daftar utama. Lanjutkan?">@csrf @method('DELETE')<button class="btn btn-outline-danger" type="submit">Hapus</button></form>@endif
                    @if($canAssignCase)<a href="{{ route('assignments.cases.index', ['case_id' => $case->id]) }}" class="btn btn-outline-secondary">Atur Penugasan</a>@endif
                </div>
            @endif
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Informasi Kasus</h2></div>
                    <div class="sibk-panel__body p-4 pt-2">
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6"><span class="d-block text-muted small">Status</span><span class="sibk-badge sibk-badge--primary">{{ $case->status->label }}</span></div>
                            <div class="col-sm-6"><span class="d-block text-muted small">Tanggal layanan</span><strong>{{ $case->service_date->locale('id')->translatedFormat('d F Y') }}</strong></div>
                            <div class="col-sm-6"><span class="d-block text-muted small">Sumber</span><strong>{{ $case->source->label }}</strong></div>
                            <div class="col-sm-6"><span class="d-block text-muted small">Bidang layanan</span><strong>{{ $case->serviceField->label }}</strong></div>
                            <div class="col-sm-6"><span class="d-block text-muted small">Perujuk</span><strong>{{ $case->referrer ?: '-' }}</strong></div>
                        </div>
                        <h3 class="fs-6 fw-bold">Informasi awal</h3>
                        <p class="text-break">{{ $case->initial_info }}</p>
                        <h3 class="fs-6 fw-bold">Tindakan awal</h3>
                        <p class="text-break">{{ $case->initial_action ?: '-' }}</p>
                        <h3 class="fs-6 fw-bold">Ringkasan Penanganan untuk Waka</h3>
                        <p class="text-break mb-0">{{ $case->waka_summary ?: '-' }}</p>
                        @if($canViewInternal)
                            <hr>
                            <h3 class="fs-6 fw-bold">Catatan internal</h3>
                            <p class="text-break mb-0">{{ $case->internal_note ?: '-' }}</p>
                        @endif
                    </div>
                </div>

                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Riwayat Tindak Lanjut</h2></div>
                    <div class="table-responsive">
                        <table class="table sibk-table mb-0">
                            <thead><tr><th>Rencana</th><th>Jenis</th><th>Status</th><th>Pelaksana</th><th>Hasil</th><th></th></tr></thead>
                            <tbody>
                            @forelse($case->followUps->sortByDesc('planned_date') as $followUp)
                                <tr>
                                    <td>{{ $followUp->planned_date->locale('id')->translatedFormat('d M Y') }}</td>
                                    <td>{{ $followUp->type->label }}</td>
                                    <td><span class="sibk-badge sibk-badge--primary">{{ $followUp->status->label }}</span></td>
                                    <td>{{ $followUp->recorder->name }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($followUp->result ?: '-', 80) }}</td>
                                    <td>
                                        @if($canManageCase && $followUp->recorded_by === auth()->id())
                                            <a href="{{ route('cases.follow-ups.edit', [$case, $followUp]) }}" class="fw-semibold text-decoration-none">Ubah</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada tindak lanjut.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Data e-Tatib Tertaut</h2></div>
                    <div class="table-responsive">
                        <table class="table sibk-table mb-0">
                            <thead><tr><th>Waktu</th><th>Pelanggaran</th><th>Kategori</th><th>Poin</th><th>Status sumber</th></tr></thead>
                            <tbody>
                            @forelse($case->etatibRecords as $record)
                                <tr>
                                    <td>{{ $record->occurred_at->locale('id')->translatedFormat('d M Y H:i') }}</td>
                                    <td>{{ $record->violation_type }}</td>
                                    <td>{{ $record->category ?: '-' }}</td>
                                    <td>{{ $record->points ?? '-' }}</td>
                                    <td>{{ $record->source_status ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada record e-Tatib tertaut.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Penugasan Kasus</h2></div>
                    <div class="sibk-panel__body p-4 pt-2">
                        @forelse($case->assignments->sortByDesc('effective_from') as $assignment)
                            <div class="border-bottom py-2">
                                <strong class="d-block">{{ $assignment->teacher->name }}</strong>
                                <span class="small text-muted">{{ $assignment->assignment_type === 'owner' ? 'Penanggung jawab' : 'Kewenangan tambahan' }} &bull; {{ $assignment->effective_from->format('d-m-Y') }} s.d. {{ $assignment->effective_until?->format('d-m-Y') ?? 'sekarang' }}</span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Belum ada penugasan.</p>
                        @endforelse
                    </div>
                </div>

                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Koordinasi Waka</h2></div>
                    <div class="sibk-panel__body p-4 pt-2">
                        @forelse($case->coordinations->sortByDesc('coordinated_at') as $coordination)
                            <div class="border-bottom py-3">
                                <div class="d-flex justify-content-between gap-2"><strong>{{ $coordination->waka->name }}</strong><span class="sibk-badge sibk-badge--primary">{{ $coordination->status->label }}</span></div>
                                <p class="small mt-2 mb-1">{{ $coordination->result ?: $coordination->coordination_need }}</p>
                            </div>
                        @empty
                            <p class="text-muted">Belum ada koordinasi.</p>
                        @endforelse

                        @if($canCoordinateCase && $wakaUsers->isNotEmpty())
                            <form action="{{ route('cases.coordinations.store', $case) }}" method="POST" class="mt-3">
                                @csrf
                                <label class="form-label" for="waka_user_id">Tujuan koordinasi</label>
                                <select class="form-select mb-2" id="waka_user_id" name="waka_user_id" required>
                                    <option value="">Pilih Waka Kesiswaan</option>
                                    @foreach($wakaUsers as $waka)<option value="{{ $waka->id }}">{{ $waka->name }}</option>@endforeach
                                </select>
                                <textarea class="form-control mb-2" name="result" rows="3" placeholder="Ringkasan hasil koordinasi yang dilakukan di luar aplikasi" required></textarea>
                                <div class="form-text mb-2">Aplikasi hanya menyimpan ringkasan hasil. Percakapan dan persetujuan dilakukan di luar aplikasi.</div>
                                <button type="submit" class="btn btn-outline-primary w-100">Simpan Hasil Koordinasi</button>
                            </form>
                        @endif
                    </div>
                </div>

                @if($case->closed_at)
                    <div class="sibk-panel">
                        <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Penyelesaian</h2></div>
                        <div class="sibk-panel__body p-4 pt-2">
                            <p><span class="d-block text-muted small">Tanggal selesai</span>{{ $case->closed_at->locale('id')->translatedFormat('d F Y') }}</p>
                            <p><span class="d-block text-muted small">Hasil akhir</span>{{ $case->final_result }}</p>
                            <p><span class="d-block text-muted small">Ringkasan</span>{{ $case->resolution_summary }}</p>
                            <p class="mb-0"><span class="d-block text-muted small">Rencana lanjutan</span>{{ $case->continued_plan ?: '-' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
