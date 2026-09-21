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

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Layanan</th>
                <th>Permasalahan</th>
                <th>Penanganan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td>{{ $row['number'] }}</td>
                    <td>{{ $row['date_label'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['classroom'] }}</td>
                    <td>{{ $row['service'] }}</td>
                    <td>{{ $row['problem'] }}</td>
                    <td>{{ $row['handling'] }}</td>
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
