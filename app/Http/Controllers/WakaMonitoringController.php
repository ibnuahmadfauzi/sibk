<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WakaMonitoringRequest;
use App\Http\Requests\WakaReportRequest;
use App\Models\User;
use App\Services\WakaMonitoringService;
use App\Services\WakaStudentCaseService;
use App\Support\ServiceRecordStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WakaMonitoringController extends Controller
{
    public function __construct(
        private readonly WakaMonitoringService $service,
        private readonly WakaStudentCaseService $students,
    ) {}

    /**
     * Tampilkan halaman portal monitoring Waka.
     * Mencatat audit event waka.monitoring.viewed setelah response siap.
     */
    public function students(WakaMonitoringRequest $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $params = $request->normalizedParams();

        $paginator = $this->students->paginateSafe($user, $params)
            ->appends($request->except('page'));
        $this->service->auditViewed($user, 'students', $params, $paginator->count(), $request);

        return view('pages.waka.students', [
            'rows' => $paginator->getCollection(),
            'paginator' => $paginator,
            'params' => $params,
            'statuses' => ServiceRecordStatus::labels(),
        ]);
    }

    public function reports(WakaReportRequest $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $tab = $request->tab();
        $paginator = null;
        $params = [];

        if ($tab === 'penanganan') {
            $params = $request->monitoringParams();
            $paginator = $this->service->paginateSafe($user, $params)
                ->withPath(route('waka.reports'))
                ->appends(['tab' => 'penanganan', ...$request->except(['page', 'tab'])]);
            $this->service->auditViewed($user, 'reports.penanganan', $params, $paginator->count(), $request);
        } elseif ($tab === 'rekap') {
            $params = $request->recapParams();
            $this->service->auditViewed($user, 'reports.rekap', $params, 0, $request);
        } else {
            $this->service->auditViewed($user, 'reports.laporan-akhir', [], 0, $request);
        }

        return view('pages.waka.reports', [
            'tab' => $tab,
            'rows' => $paginator?->getCollection() ?? collect(),
            'paginator' => $paginator,
            'params' => $params,
            'statuses' => ServiceRecordStatus::labels(),
        ]);
    }

    public function legacyHandling(WakaMonitoringRequest $request): RedirectResponse
    {
        $params = Arr::only($request->validated(), ['period', 'status', 'sort', 'direction', 'page']);

        return redirect()->route('waka.reports', ['tab' => 'penanganan', ...$params]);
    }

    /**
     * Ekspor data monitoring sebagai CSV.
     * Audit event waka.monitoring.exported dicatat SEBELUM stream dikirim.
     */
    public function export(WakaMonitoringRequest $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $params = $request->normalizedParams();

        $rows = $this->service->exportCsvRows($user, $params);

        // Audit SEBELUM stream dikirim ke client
        $this->service->auditExported($user, $params, $rows->count(), 'csv', $request);

        $filename = sprintf('monitoring-waka-%s.csv', now()->format('Ymd-His'));
        $headers = $rows->isNotEmpty() ? array_keys($rows->first()) : [];

        return response()->streamDownload(function () use ($rows, $headers): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }
            fwrite($output, "\xEF\xBB\xBF"); // BOM untuk Excel
            if (! empty($headers)) {
                fputcsv($output, $headers, ',', '"', '');
            }
            foreach ($rows as $row) {
                fputcsv($output, array_values($row), ',', '"', '');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
