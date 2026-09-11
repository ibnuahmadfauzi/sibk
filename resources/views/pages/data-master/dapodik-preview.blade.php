@extends('layouts.app-2')

@section('page-title', 'Pratinjau Dapodik - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-501-DAPODIK-PREVIEW">
        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <h1 class="mb-1">Pratinjau Pencocokan Dapodik</h1>
                <p class="mb-0">Periksa hasil sebelum diterapkan. Data operasional belum berubah.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('data-master.index') }}">Kembali</a>
        </div>

        <div class="alert alert-info" role="status">
            <strong>{{ $syncRun->is_full_snapshot ? 'Snapshot penuh' : 'Snapshot parsial' }}.</strong>
            Generasi {{ $syncRun->preview_generation }} · {{ $syncRun->received_count }} item ·
            berakhir {{ $syncRun->preview_expires_at?->locale('id')->translatedFormat('d M Y, H.i') }}.
            Data persiapan ditandai <strong>Belum terverifikasi Dapodik</strong> sampai penerapan berhasil.
        </div>

        @if($syncRun->is_full_snapshot)
            <section class="sibk-panel border-0 mb-4" aria-labelledby="deactivation-plan-title">
                <div class="sibk-panel__body p-4">
                    <h2 class="fs-6 fw-bold mb-2" id="deactivation-plan-title">Rencana Penonaktifan</h2>
                    <p class="text-muted small mb-3">
                        Snapshot penuh akan menonaktifkan hanya data terverifikasi Dapodik yang tercantum berikut ini.
                    </p>
                    @forelse($syncRun->deactivation_plan as $planned)
                        @php
                            $plannedEntity = match($planned['entity_type']) {
                                'classroom' => 'Rombel',
                                'student' => 'Murid',
                                default => 'Keanggotaan',
                            };
                        @endphp
                        <div class="border rounded-3 p-3 mb-2">
                            <strong>{{ $plannedEntity }} internal #{{ $planned['target_id'] }}</strong>
                            <span class="text-muted small d-block">
                                Fingerprint: {{ substr($planned['target_fingerprint'], 0, 12) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Tidak ada data yang direncanakan untuk dinonaktifkan.</p>
                    @endforelse
                </div>
            </section>
        @else
            <div class="alert alert-secondary" role="status">
                Snapshot parsial tidak akan menonaktifkan data yang tidak tercantum.
            </div>
        @endif

        <div class="sibk-panel border-0 mb-4">
            <div class="table-responsive">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>Jenis</th>
                            <th>Data sumber</th>
                            <th>Hasil</th>
                            <th>Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($syncRun->previewItems as $item)
                            @php
                                $hasDerivedConflict = $item->decision === 'conflict' && $item->match_status !== 'conflict';
                                [$statusLabel, $statusTone] = $hasDerivedConflict ? ['Konflik', 'danger'] : match($item->match_status) {
                                    'exact_match' => ['Cocok', 'success'],
                                    'new_record' => ['Baru', 'info'],
                                    'changed' => ['Berubah', 'warning'],
                                    'needs_mapping' => ['Perlu dipetakan', 'warning'],
                                    default => ['Konflik', 'danger'],
                                };
                                $entityLabel = match($item->entity_type) {
                                    'academic_year' => 'Tahun ajaran',
                                    'classroom' => 'Rombel',
                                    'student' => 'Murid',
                                    default => 'Keanggotaan',
                                };
                            @endphp
                            <tr>
                                <td>{{ $entityLabel }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $item->safe_fields['name'] ?? $item->safe_fields['nisn'] ?? $item->source_identifier }}</div>
                                    <div class="text-muted small">ID sumber: {{ $item->source_identifier }}</div>
                                </td>
                                <td><span class="sibk-badge sibk-badge--{{ $statusTone }}">{{ $statusLabel }}</span></td>
                                <td>
                                    @if($item->match_status === 'needs_mapping' && in_array($item->entity_type, ['academic_year', 'classroom'], true))
                                        <form method="POST" action="{{ route('data-master.dapodik.previews.items.update', [$syncRun, $item]) }}" class="d-flex flex-column gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="decision_revision" value="{{ $item->decision_revision }}">
                                            <label class="visually-hidden" for="candidate-{{ $item->id }}">Kandidat pencocokan</label>
                                            <select class="form-select form-select-sm" id="candidate-{{ $item->id }}" name="candidate_id">
                                                <option value="">Pilih data sementara</option>
                                                @foreach($item->entity_type === 'academic_year' ? $academicYearCandidates : $classroomCandidates as $candidate)
                                                    <option value="{{ $candidate->id }}" @selected($item->decision_candidate_id === $candidate->id)>
                                                        {{ $candidate->name }}@if($item->entity_type === 'classroom' && $candidate->academicYear) · {{ $candidate->academicYear->name }}@endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button class="btn btn-sm btn-outline-primary" name="decision" value="map_existing" type="submit">Cocokkan</button>
                                                <button class="btn btn-sm btn-outline-secondary" name="decision" value="create_new" type="submit">Buat data resmi baru</button>
                                            </div>
                                        </form>
                                    @elseif($item->match_status === 'conflict')
                                        <span class="text-danger small">Konflik tidak dapat dipaksa melalui halaman ini.</span>
                                    @elseif($hasDerivedConflict)
                                        <span class="text-danger small">Konflik turunan pada keanggotaan murid. Periksa pemetaan tahun ajaran dan rombel; hasil belum dapat diterapkan.</span>
                                    @else
                                        <span class="text-muted small">Tidak memerlukan keputusan manual.</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('data-master.dapodik.previews.apply', $syncRun) }}" class="d-flex justify-content-end">
            @csrf
            <input type="hidden" name="decision_revision" value="{{ $syncRun->decision_revision }}">
            <button type="submit" class="btn btn-primary" @disabled($syncRun->conflict_count > 0)>
                Terapkan Hasil Pencocokan
            </button>
        </form>
    </div>
@endsection
