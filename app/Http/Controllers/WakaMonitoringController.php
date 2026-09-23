<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WakaMonitoringRequest;
use App\Models\User;
use App\Services\WakaMonitoringService;
use App\Services\WakaStudentCaseService;
use App\Support\ServiceRecordStatus;
use Illuminate\Contracts\View\View;

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
}
