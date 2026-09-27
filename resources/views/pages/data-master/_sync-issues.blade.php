<section class="sibk-panel mb-4" aria-labelledby="sync-issues-title">
    <div class="sibk-panel__header">
        <div>
            <h2 class="sibk-panel__title" id="sync-issues-title">Masalah Sinkronisasi Belum Selesai</h2>
            <p class="sibk-panel__subtitle">{{ $syncIssues->total() }} masalah dari Dapodik dan e-Tatib. Angka ini sama dengan ringkasan dashboard; angka pada riwayat sinkronisasi tetap menunjukkan hasil saat proses berlangsung.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table sibk-table mb-0">
            <thead><tr><th scope="col">Sumber</th><th scope="col">Data</th><th scope="col">Masalah</th><th scope="col">Tindak lanjut</th></tr></thead>
            <tbody>
                @forelse($syncIssues as $issue)
                    @php($canMap = $issue->entity_type === 'etatib_record' && in_array($issue->issue_code, ['student_not_found', 'student_name_mismatch'], true))
                    @php($dataLabel = match ($issue->entity_type) { 'academic_year' => 'Tahun ajaran', 'student' => 'Murid', 'classroom' => 'Kelas', 'membership' => 'Keanggotaan kelas', 'etatib_record' => 'Pelanggaran e-Tatib', default => 'Data sumber' })
                    <tr>
                        <td>{{ $issue->syncRun?->source === 'etatib' ? 'e-Tatib' : 'Dapodik' }}</td>
                        <td>
                            {{ $issue->input_name ?: ($issue->nisn ? 'NISN '.$issue->nisn : ($localTargets[$issue->entity_type.':'.(int) substr((string) $issue->source_identifier, 6)] ?? $dataLabel.' '.str_replace('local:', '#', (string) $issue->source_identifier))) }}
                            @if($issue->input_name && $issue->nisn)<span class="d-block small text-muted">NISN {{ $issue->nisn }}</span>@endif
                        </td>
                        <td>{{ $issue->summary }}</td>
                        <td>
                            @if($canMap)
                                <a href="{{ route('data-master.etatib.conflicts.index') }}">Cocokkan identitas</a>
                            @elseif($issue->issue_code === 'student_classroom_mismatch')
                                Periksa kelas di sumber e-Tatib dan riwayat murid. Perbaiki data sumber yang keliru, lalu sinkronkan ulang.
                            @elseif($issue->issue_code === 'unmatched_local_record')
                                Data lokal tetap disimpan. Periksa dengan sumber resmi; belum ada aksi penyelesaian untuk catatan ini di aplikasi.
                            @else
                                Periksa data sumber dan hasil sinkronisasi. Catatan ini belum memiliki aksi penyelesaian di aplikasi.
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada masalah sinkronisasi yang belum selesai.</td></tr>
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
            <p class="sibk-panel__subtitle">Jumlah konflik di sini adalah hasil saat sinkronisasi berlangsung, bukan jumlah yang masih terbuka.</p>
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
