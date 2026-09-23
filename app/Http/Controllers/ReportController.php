<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\OperationalReportRequest;
use App\Models\User;
use App\Services\OperationalReportRecapService;
use App\Services\ReportDocumentExporter;
use App\Services\ReportSignatoryResolver;
use App\Services\WakaMonitoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(
        OperationalReportRequest $request,
        OperationalReportRecapService $service,
        WakaMonitoringService $wakaMonitoring,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $report = $service->paginateForUi($user, $request->filters());

        if ($user->hasRole('waka_kesiswaan')) {
            $wakaMonitoring->auditViewed(
                $user,
                'reports.layanan',
                $report['filters'],
                $report['rows']->count(),
                $request,
            );
        }

        return view('pages.reports.index', [
            'report' => $report,
        ]);
    }

    public function preview(
        OperationalReportRequest $request,
        OperationalReportRecapService $service,
        ReportSignatoryResolver $signatories,
    ): View {
        /** @var User $user */
        $user = $request->user();

        return view('pages.reports.preview', [
            'report' => $service->allForDocument($user, $request->filters()),
            'signatories' => $signatories->forRecap(),
        ]);
    }

    public function recordPreview(
        Request $request,
        string $type,
        int $id,
        OperationalReportRecapService $service,
        ReportSignatoryResolver $signatories,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $record = $service->findRecord($user, $type, $id);
        abort_unless($user->can('view', $record), 403);

        return view('pages.reports.record-preview', [
            'record' => $service->recordForDocument($record),
            'signatories' => $signatories->forRecord($record),
        ]);
    }

    public function export(
        OperationalReportRequest $request,
        OperationalReportRecapService $service,
        ReportDocumentExporter $exporter,
    ): BinaryFileResponse {
        /** @var User $user */
        $user = $request->user();
        $file = $exporter->export(
            (string) $request->validated('format'),
            $service->allForDocument($user, $request->filters()),
        );

        return response()
            ->download(
                $file['path'],
                $file['filename'],
                ['Content-Type' => $file['content_type']],
            )
            ->deleteFileAfterSend(true);
    }
}
