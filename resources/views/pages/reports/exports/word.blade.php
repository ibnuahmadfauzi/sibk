<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Layanan BK</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.5cm;
        }

        body {
            color: #111;
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }

        .sibk-document-letterhead {
            border-bottom: 3px double #111;
            margin-bottom: 20px;
            padding-bottom: 10px;
            text-align: center;
        }

        .sibk-document-letterhead p,
        .sibk-document-letterhead h1,
        .sibk-document-letterhead h2 {
            margin: 3px 0;
        }

        .sibk-document-letterhead__agency,
        .sibk-document-letterhead__school,
        .sibk-document-letterhead__title {
            font-weight: bold;
            text-transform: uppercase;
        }

        .sibk-report-summary {
            margin: 0 0 16px;
        }

        .sibk-report-summary__item {
            background: #edf4fc;
            border-left: 3px solid #2f6fc6;
            display: inline-block;
            margin: 0 8px 6px 0;
            padding: 7px 12px;
        }

        .sibk-report-summary__value {
            font-size: 13pt;
            margin-right: 4px;
        }

        .sibk-report-summary__label {
            color: #444;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #555;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #dce6f1;
        }

        .sibk-document-signatures {
            margin-top: 35px;
            width: 100%;
        }

        .sibk-document-signatures__column {
            display: inline-block;
            text-align: center;
            vertical-align: top;
            width: 48%;
        }

        .sibk-document-signatures__role {
            margin-bottom: 55px;
        }

        .sibk-document-signatures__name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    @include('pages.reports.print._letterhead', [
        'title' => 'Laporan Layanan Bimbingan dan Konseling',
        'subtitle' => $filterSummary,
    ])

    @include('pages.reports._summary', [
        'items' => $report['summary'],
    ])

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Hari / Tanggal</th>
                <th>Nama / Kelas</th>
                <th>Jenis Layanan</th>
                <th>Permasalahan</th>
                <th>Penanganan</th>
                <th>Tindak Lanjut / Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td>{{ $row['number'] }}</td>
                    <td>
                        <strong>{{ $row['date']->locale('id')->translatedFormat('l') }}</strong><br>
                        {{ $row['date']->locale('id')->translatedFormat('d F Y') }}
                    </td>
                    <td>
                        <strong>{{ mb_strtoupper($row['name']) }}</strong><br>
                        {{ $row['classroom'] }}
                    </td>
                    <td>
                        <strong>{{ $row['service'] }}</strong><br>
                        {{ $row['service_field'] }}
                    </td>
                    <td>{{ $row['problem'] }}</td>
                    <td>
                        <strong>{{ $row['handling'] }}</strong><br>
                        {{ $row['detail_label'] }}: {{ $row['detail_note'] }}
                    </td>
                    <td>{{ $row['follow_up_label'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Tidak ada data sesuai filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pages.reports.print._signature-block', [
        'generatedAt' => $report['generated_at'],
    ])
</body>
</html>
