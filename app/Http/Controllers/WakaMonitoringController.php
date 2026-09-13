<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WakaMonitoringRequest;
use App\Models\User;
use App\Services\WakaMonitoringService;
use App\Support\ServiceRecordStatus;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WakaMonitoringController extends Controller
{
    public function __construct(private readonly WakaMonitoringService $service) {}

    /**
     * Tampilkan halaman portal monitoring Waka.
     * Mencatat audit event waka.monitoring.viewed setelah response siap.
     */
    public function index(WakaMonitoringRequest $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $params = $request->normalizedParams();

        $paginator = $this->service->paginateSafe($user, $params);
        $mode = $request->routeIs('waka.monitoring.students') ? 'students' : 'reports.penanganan';

        // Catat audit setelah query berhasil, sebelum render view
        $this->service->auditViewed($user, $mode, $params, $paginator->count(), $request);

        return view('pages.waka.monitoring', [
            'rows' => $paginator->getCollection(),
            'paginator' => $paginator->withPath(route('waka.monitoring.handling'))->appends($request->except('page')),
            'params' => $params,
            'statuses' => ServiceRecordStatus::labels(),
            'sortOptions' => WakaMonitoringRequest::HANDLING_SORT_ALLOWLIST,
        ]);
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
