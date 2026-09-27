<section class="sibk-panel mb-4" aria-labelledby="sync-issues-title">
    <div class="sibk-panel__header">
        <div>
            <h2 class="sibk-panel__title" id="sync-issues-title">Masalah Sinkronisasi Belum Selesai</h2>
            <p class="sibk-panel__subtitle">{{ $syncIssues->total() }} data memiliki masalah.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table sibk-table mb-0">
            <thead><tr><th scope="col">Sumber</th><th scope="col">Data</th><th scope="col">Masalah</th><th scope="col">Pemeriksaan</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                @forelse($syncIssues as $issue)
                    @php($dataLabel = match ($issue->entity_type) { 'academic_year' => 'Tahun ajaran', 'student' => 'Murid', 'classroom' => 'Kelas', 'membership' => 'Keanggotaan kelas', 'etatib_record' => 'Pelanggaran e-Tatib', default => 'Data sumber' })
                    <tr>
                        <td>{{ $issue->syncRun?->source === 'etatib' ? 'e-Tatib' : 'Dapodik' }}</td>
                        <td>
                            {{ $issue->input_name ?: ($issue->nisn ? 'NISN '.$issue->nisn : ($localTargets[$issue->entity_type.':'.(int) substr((string) $issue->source_identifier, 6)] ?? $dataLabel.' '.str_replace('local:', '#', (string) $issue->source_identifier))) }}
                            @if($issue->input_name && $issue->nisn)<span class="d-block small text-muted">NISN {{ $issue->nisn }}</span>@endif
                        </td>
                        <td>{{ $issue->summary }}</td>
                        <td>
                            {{ match (data_get($issue->details, 'review.status')) { 'reviewed' => 'Terakhir diperiksa', 'source_correction' => 'Perlu koreksi sumber', default => 'Belum diperiksa' } }}
                            @if(data_get($issue->details, 'review.reviewed_at'))
                                <span class="d-block small text-muted">{{ \Carbon\Carbon::parse(data_get($issue->details, 'review.reviewed_at'))->locale('id')->translatedFormat('d M Y, H.i') }}</span>
                            @endif
                        </td>
                        <td><a class="btn btn-sm p-0 sibk-icon-button sibk-report-control" href="{{ route('data-master.sync-issues.show', $issue) }}" aria-label="Periksa {{ $issue->input_name ?: ($issue->nisn ?: $dataLabel) }}" title="Periksa">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.4 15.4 4.3 4.3"/></svg>
                        </a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada masalah sinkronisasi yang belum selesai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@if($syncIssues->hasPages())
    <div class="mt-3">{{ $syncIssues->links() }}</div>
@endif

<section class="sibk-panel mt-4" aria-labelledby="sync-runs-title">
    <div class="sibk-panel__header">
        <div>
            <h2 class="sibk-panel__title" id="sync-runs-title">Riwayat Sinkronisasi</h2>
            <p class="sibk-panel__subtitle">Hasil sinkronisasi sebelumnya.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table sibk-table mb-0">
            <thead><tr><th scope="col">Waktu</th><th scope="col">Sumber</th><th scope="col">Status</th><th scope="col">Hasil</th></tr></thead>
            <tbody>
                @forelse($syncRuns as $run)
                    <tr>
                        <td>{{ $run->started_at?->locale('id')->translatedFormat('d M Y, H.i') ?? '-' }}</td>
                        <td>{{ match ($run->source) { 'etatib' => 'e-Tatib', 'dapodik' => 'Dapodik', 'api_siswa' => 'API Siswa', default => 'Sumber lain' } }}</td>
                        <td>{{ match ($run->status) { 'succeeded' => 'Berhasil', 'warning' => 'Peringatan', 'failed' => 'Gagal', 'running' => 'Berjalan', 'preview_ready' => 'Pratinjau siap', default => 'Digantikan' } }}</td>
                        <td>{{ $run->summary ?: $run->processed_count.' diproses, '.$run->conflict_count.' konflik' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat sinkronisasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@if($syncRuns->hasPages())
    <div class="mt-3">{{ $syncRuns->links() }}</div>
@endif
