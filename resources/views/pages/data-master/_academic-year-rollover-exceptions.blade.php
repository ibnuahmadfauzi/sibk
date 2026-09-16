@if($rolloverSummary?->sourceYearId !== null)
    <section class="sibk-panel mb-4" aria-labelledby="academic-year-rollover-title">
        <div class="sibk-panel__header p-4 border-0 pb-0">
            <div>
                <h2 class="sibk-panel__title mb-1" id="academic-year-rollover-title">Perlu Konfirmasi</h2>
                <p class="sibk-panel__subtitle text-muted small mb-0">
                    Murid aktif dari {{ $rolloverSummary->sourceYearName }} yang belum memiliki penempatan aktif pada tahun target.
                </p>
            </div>
            <span class="sibk-badge sibk-badge--{{ $rolloverSummary->needsConfirmationCount() > 0 ? 'warning' : 'success' }}">
                {{ $rolloverSummary->needsConfirmationCount() }} murid
            </span>
        </div>
        <div class="sibk-panel__body p-4">
            <p class="text-muted small">
                Daftar ini hanya penanda pemeriksaan. Sistem tidak menentukan naik kelas, tinggal kelas, lulus, pindah, atau keluar.
            </p>
            <div class="table-responsive">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Murid</th>
                            <th>Rombel Sebelumnya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rolloverSummary->needsConfirmation as $row)
                            <tr>
                                <td>{{ $row['nisn'] }}</td>
                                <td class="fw-semibold">{{ $row['student_name'] }}</td>
                                <td>{{ $row['source_classroom'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">
                                    Semua murid aktif dari tahun sebelumnya sudah memiliki penempatan pada tahun target.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif
