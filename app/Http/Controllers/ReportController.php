<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\User;
use App\Policies\ReportPolicy;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $service, ReportPolicy $policy): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($policy->viewAny($user), 403);

        return view('pages.reports.index', ['reports' => $service->catalogFor($user)]);
    }

    public function preview(ReportRequest $request, ReportService $service): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('pages.reports.preview', ['report' => $service->build($user, $request->validated())]);
    }

    public function export(ReportRequest $request, ReportService $service, ReportPolicy $policy): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        abort_unless($policy->export($user, (string) $data['type']), 403);
        $report = $service->exportRows($user, $data);
        $filename = sprintf('%s-%s.csv', $report['id'], now()->format('Ymd-His'));

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
