<div class="table-responsive sibk-operational-report-table">
    <table class="table sibk-table align-middle mb-0">
        <thead><tr>@foreach($report['columns'] as $column)<th scope="col">{{ $column }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach($report['rows'] as $row)
                @php
                    $cells = match ($report['tab']) {
                        'pelanggaran' => [$row['initials'], $row['masked_nisn'], $row['classroom'], $row['violation_count'], $row['total_points'], $row['latest_violation'].' · '.$row['latest_date']],
                        'layanan' => [$row['initials'], $row['masked_nisn'], $row['classroom'], $row['case_count'], $row['consultation_count'], $row['follow_up_count'], $row['open_follow_up_count'], $row['latest_service_date']],
                        'prestasi' => [$row['initials'], $row['masked_nisn'], $row['classroom'], $row['achievement_count'], $row['verified_count'], $row['highest_verified_level'], $row['latest_achievement'].' · '.$row['latest_date']],
                    };
                @endphp
                <tr>@foreach($cells as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @endforeach
        </tbody>
    </table>
</div>
