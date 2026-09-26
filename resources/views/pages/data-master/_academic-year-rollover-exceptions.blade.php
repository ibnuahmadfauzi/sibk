@if($rolloverSummary?->sourceYearId !== null && $rolloverSummary->needsConfirmationCount() > 0)
    <section class="sibk-panel mb-4" aria-labelledby="academic-year-rollover-title">
        <div class="sibk-panel__header p-4 border-0 pb-0">
            <div>
                <h2 class="sibk-panel__title mb-1" id="academic-year-rollover-title">Murid Tahun Sebelumnya Belum Tercantum</h2>
                <p class="sibk-panel__subtitle text-muted small mb-0">
                    Murid dari {{ $rolloverSummary->sourceYearName }} belum tercantum di {{ $rolloverTargetYear->name }}.
                </p>
            </div>
            <span class="sibk-badge sibk-badge--{{ $rolloverSummary->needsConfirmationCount() > 0 ? 'warning' : 'success' }}">
                {{ $rolloverSummary->needsConfirmationCount() }} murid
            </span>
        </div>
        <div class="sibk-panel__body p-4">
            <p class="text-muted small mb-3">
                Periksa statusnya pada data sekolah. Jika masih bersekolah, pastikan murid dan rombelnya ada pada API Siswa tahun ini, lalu impor lagi. Jika sudah lulus atau pindah, tidak perlu menempatkannya di rombel baru.
            </p>
            <a class="btn btn-outline-primary btn-sm" href="#api-siswa-import-title">Periksa API Siswa</a>
            <details>
                <summary class="fw-semibold text-primary py-3">Lihat daftar {{ $rolloverSummary->needsConfirmationCount() }} murid</summary>
            <div class="table-responsive mt-3">
                <table class="table sibk-table mb-0">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Murid</th>
                            <th>Rombel Sebelumnya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rolloverSummary->needsConfirmation as $row)
                            <tr>
                                <td>{{ $row['nisn'] }}</td>
                                <td class="fw-semibold">{{ $row['student_name'] }}</td>
                                <td>{{ $row['source_classroom'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </details>
        </div>
    </section>
@endif
