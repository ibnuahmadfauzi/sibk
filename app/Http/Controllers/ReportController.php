<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\OperationalReportRequest;
use App\Http\Requests\ReportRequest;
use App\Models\User;
use App\Policies\ReportPolicy;
use App\Services\OperationalReportRecapService;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(OperationalReportRequest $request, OperationalReportRecapService $service): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('pages.reports.index', ['report' => $service->build($user, $request->filters())]);
    }

    public function preview(ReportRequest $request, ReportService $service): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('pages.reports.preview', ['report' => $service->build($user, $request->validated())]);
    }

    public function export(
        OperationalReportRequest $request,
        ReportService $legacyReports,
        OperationalReportRecapService $operationalReports,
        ReportPolicy $policy,
    ): StreamedResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->filters();
        if (isset($data['tab'])) {
            abort_unless($policy->exportTab($user, (string) $data['tab']), 403);
            $report = $operationalReports->exportRows($user, $data);
        } else {
            abort_unless($policy->export($user, (string) $data['type']), 403);
            $report = $legacyReports->exportRows($user, $data);
        }
        $format = (string) $request->validated('format');
        $filename = sprintf('%s-%s.%s', $report['id'], now()->format('Ymd-His'), $format);

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $report['columns'], ',', '"', '');
            foreach ($report['rows'] as $row) {
                fputcsv($output, collect($row['cells'])->map(
                    fn (array $cell): string => $this->csvValue((string) $cell['value']),
                )->all(), ',', '"', '');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvValue(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/u', $value) === 1 ? "'".$value : $value;
    }
}
