<div class="sibk-operational-report-cards">
    @foreach($report['rows'] as $row)
        @php
            $details = match ($report['tab']) {
                'pelanggaran' => ['NISN' => $row['masked_nisn'], 'Kelas' => $row['classroom'], 'Pelanggaran' => $row['violation_count'], 'Total poin' => $row['total_points'], 'Terakhir' => $row['latest_violation'].' · '.$row['latest_date']],
                'layanan' => ['NISN' => $row['masked_nisn'], 'Kelas' => $row['classroom'], 'Kasus' => $row['case_count'], 'Konsultasi' => $row['consultation_count'], 'Tindak lanjut' => $row['follow_up_count'], 'Perlu tindak lanjut' => $row['open_follow_up_count'], 'Terakhir' => $row['latest_service_date']],
                'prestasi' => ['NISN' => $row['masked_nisn'], 'Kelas' => $row['classroom'], 'Prestasi' => $row['achievement_count'], 'Terverifikasi' => $row['verified_count'], 'Tingkat tertinggi' => $row['highest_verified_level'], 'Terbaru' => $row['latest_achievement'].' · '.$row['latest_date']],
            };
        @endphp
        <article class="sibk-panel sibk-operational-report-card p-3">
            <h3 class="h6 mb-1">{{ $row['initials'] }}</h3>
            @if($row['identity_badge'] ?? null)<span class="sibk-badge sibk-badge--warning mb-3">{{ $row['identity_badge'] }}</span>@endif
            <dl class="mb-0">
                @foreach($details as $label => $value)
                    <div class="d-flex justify-content-between gap-3 py-2"><dt>{{ $label }}</dt><dd class="mb-0 text-end">{{ $value }}</dd></div>
                @endforeach
            </dl>
        </article>
    @endforeach
</div>
