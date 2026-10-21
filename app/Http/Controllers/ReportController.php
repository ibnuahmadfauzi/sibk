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
use Illuminate\Validation\ValidationException;
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
        $filters = $request->filters();
        foreach (['semester', 'date_from', 'date_to'] as $field) {
            unset($filters[$field]);
        }
        $report = $service->paginateForUi($user, $filters);
        $recapPeriod = $report['period'];
        $recapPeriodError = null;
        if ($report['can_view_document'] && $recapPeriod === null) {
            try {
                $recapPeriod = $service->defaultPeriodFor($report['academic_year']);
            } catch (ValidationException $exception) {
                $recapPeriodError = array_values($exception->errors())[0][0] ?? 'Periode rekap belum tersedia.';
            }
        }

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
            'recapPeriod' => $recapPeriod,
            'recapPeriodError' => $recapPeriodError,
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
            'embedded' => $request->boolean('embedded'),
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
        abort_unless($user->hasRole('koordinator_bk') || $user->can('view', $record), 403);

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
