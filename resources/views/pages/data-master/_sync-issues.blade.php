<section class="sibk-panel mb-4" aria-labelledby="sync-issues-title">
    @if($errors->any())<div class="alert alert-danger m-3" role="alert">{{ $errors->first() }}</div>@endif
    <div class="sibk-panel__header">
        <div>
            <h2 class="sibk-panel__title" id="sync-issues-title">Masalah Sinkronisasi Belum Selesai</h2>
            <p class="sibk-panel__subtitle">{{ $syncIssues->total() }} data memiliki masalah.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table sibk-table mb-0">
            <thead><tr><th scope="col">Sumber</th><x-sort-header name="data" label="Data" sort-param="issue_sort" direction-param="issue_direction" page-param="issue_page" /><th scope="col">Masalah</th><th scope="col">Pemeriksaan</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                @forelse($syncIssues as $issue)
                    @php($dataLabel = match ($issue->entity_type) { 'academic_year' => 'Tahun ajaran', 'student' => 'Murid', 'classroom' => 'Kelas', 'membership' => 'Keanggotaan kelas', 'etatib_record' => 'Pelanggaran e-Tatib', default => 'Data sumber' })
                    @php($issueDisplayName = in_array($issue->entity_type, ['student', 'etatib_record'], true) ? \App\Support\StudentName::display($issue->input_name) : $issue->input_name)
                    <tr>
                        <td>{{ $issue->syncRun?->source === 'etatib' ? 'e-Tatib' : 'Dapodik' }}</td>
                        <td>
                            {{ $issueDisplayName ?: ($issue->nisn ? 'NISN '.$issue->nisn : ($localTargets[$issue->entity_type.':'.(int) substr((string) $issue->source_identifier, 6)] ?? $dataLabel.' '.str_replace('local:', '#', (string) $issue->source_identifier))) }}
                            @if($issue->input_name && $issue->nisn)<span class="d-block small text-muted">NISN {{ $issue->nisn }}</span>@endif
                        </td>
                        <td>{{ $issue->summary }}</td>
                        <td>
                            {{ match (data_get($issue->details, 'review.status')) { 'reviewed' => 'Terakhir diperiksa', 'source_correction' => 'Perlu koreksi sumber', default => 'Belum diperiksa' } }}
                            @if(data_get($issue->details, 'review.reviewed_at'))
                                <span class="d-block small text-muted">{{ \Carbon\Carbon::parse(data_get($issue->details, 'review.reviewed_at'))->locale('id')->translatedFormat('d M Y, H.i') }}</span>
                            @endif
                        </td>
                        <td><button class="btn btn-sm p-0 sibk-icon-button sibk-report-control" type="button" data-sync-issue-toggle data-sync-issue-name="{{ $issueDisplayName ?: ($issue->nisn ?: $dataLabel) }}" @if(old('_sync_issue') == $issue->id) data-auto-open @endif data-detail-url="{{ route('data-master.sync-issues.show', ['issue' => $issue, 'inline' => 1]) }}" aria-controls="sync-issue-detail-{{ $issue->id }}" aria-expanded="false" aria-label="Tampilkan rincian {{ $issueDisplayName ?: ($issue->nisn ?: $dataLabel) }}" title="Tampilkan rincian">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.4 15.4 4.3 4.3"/></svg>
                        </button></td>
                    </tr>
                    <tr class="sibk-report-detail-row d-none" id="sync-issue-detail-{{ $issue->id }}"><td colspan="5"><div class="sibk-report-detail-panel" data-sync-issue-content>Memuat rincian...</div></td></tr>
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

<section class="sibk-panel mt-4" aria-labelledby="sync-decisions-title">
    <div class="sibk-panel__header">
        <div>
            <h2 class="sibk-panel__title" id="sync-decisions-title">Riwayat Keputusan Kelas</h2>
        </div>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('data-master.index', array_merge(request()->except('history_decisions', 'decision_page', 'decision_sort', 'decision_direction'), $showDecisionHistory ? [] : ['history_decisions' => 1])) }}#sync-decisions-title" aria-controls="sync-decisions-content" aria-expanded="{{ $showDecisionHistory ? 'true' : 'false' }}">{{ $showDecisionHistory ? 'Tutup riwayat' : 'Tampilkan riwayat' }}</a>
    </div>
    <div id="sync-decisions-content" @unless($showDecisionHistory) hidden @endunless>
    @if($showDecisionHistory)
    <p class="sibk-panel__subtitle px-3 pt-3 mb-0">Pilihan Admin saat setiap kejadian diperiksa.</p>
    <div class="table-responsive">
        <table class="table sibk-table mb-0">
            <thead><tr><x-sort-header name="data" label="Data" sort-param="decision_sort" direction-param="decision_direction" page-param="decision_page" /><th scope="col">Kelas yang dipilih saat itu</th><th scope="col">Hasil saat diperiksa</th><x-sort-header name="waktu" label="Waktu" sort-param="decision_sort" direction-param="decision_direction" page-param="decision_page" /><th scope="col">Aksi</th></tr></thead>
            <tbody>
                @forelse($classroomDecisions as $decision)
                    <tr>
                        <td>{{ \App\Support\StudentName::display($decision->input_name) ?: ($decision->nisn ? 'NISN '.$decision->nisn : 'Pelanggaran e-Tatib') }}</td>
                        <td>{{ data_get($decision->details, 'review.choice.classroom') ?? '-' }}<span class="d-block small text-muted">{{ data_get($decision->details, 'review.action') === 'use_school' ? 'Data sekolah' : 'Data e-Tatib' }}</span></td>
                        <td>Selesai</td>
                        <td>{{ $decision->resolved_at?->locale('id')->translatedFormat('d M Y, H.i') ?? '-' }}</td>
                        <td><button class="btn btn-sm p-0 sibk-icon-button sibk-report-control" type="button" data-sync-issue-toggle data-sync-issue-name="{{ \App\Support\StudentName::display($decision->input_name) ?: ($decision->nisn ?: 'pelanggaran e-Tatib') }}" data-detail-url="{{ route('data-master.sync-issues.show', ['issue' => $decision, 'inline' => 1]) }}" aria-controls="sync-issue-detail-{{ $decision->id }}" aria-expanded="false" aria-label="Tampilkan rincian {{ \App\Support\StudentName::display($decision->input_name) ?: ($decision->nisn ?: 'pelanggaran e-Tatib') }}" title="Tampilkan rincian">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.4 15.4 4.3 4.3"/></svg>
                        </button></td>
                    </tr>
                    <tr class="sibk-report-detail-row d-none" id="sync-issue-detail-{{ $decision->id }}"><td colspan="5"><div class="sibk-report-detail-panel" data-sync-issue-content>Memuat rincian...</div></td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada keputusan kelas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($classroomDecisions->hasPages())
        <div class="p-3">{{ $classroomDecisions->fragment('sync-decisions-title')->links() }}</div>
    @endif
    @endif
    </div>
</section>

<section class="sibk-panel mt-4" aria-labelledby="sync-runs-title">
    <div class="sibk-panel__header">
        <div>
            <h2 class="sibk-panel__title" id="sync-runs-title">Riwayat Sinkronisasi</h2>
        </div>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('data-master.index', array_merge(request()->except('history_runs', 'run_page', 'run_sort', 'run_direction'), $showRunHistory ? [] : ['history_runs' => 1])) }}#sync-runs-title" aria-controls="sync-runs-content" aria-expanded="{{ $showRunHistory ? 'true' : 'false' }}">{{ $showRunHistory ? 'Tutup riwayat' : 'Tampilkan riwayat' }}</a>
    </div>
    <div id="sync-runs-content" @unless($showRunHistory) hidden @endunless>
    @if($showRunHistory)
    <p class="sibk-panel__subtitle px-3 pt-3 mb-0">Hasil sinkronisasi sebelumnya.</p>
    <div class="table-responsive">
        <table class="table sibk-table mb-0">
            <thead><tr><x-sort-header name="waktu" label="Waktu" sort-param="run_sort" direction-param="run_direction" page-param="run_page" /><x-sort-header name="sumber" label="Sumber" sort-param="run_sort" direction-param="run_direction" page-param="run_page" /><x-sort-header name="status" label="Status" sort-param="run_sort" direction-param="run_direction" page-param="run_page" /><th scope="col">Hasil</th></tr></thead>
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
    @if($syncRuns->hasPages())
        <div class="p-3">{{ $syncRuns->fragment('sync-runs-title')->links() }}</div>
    @endif
    @endif
    </div>
</section>
