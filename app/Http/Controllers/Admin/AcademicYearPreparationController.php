<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportProvisionalRosterRequest;
use App\Http\Requests\Admin\PreviewApiSiswaRosterRequest;
use App\Http\Requests\Admin\StoreProvisionalAcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
use App\Services\ApiSiswaRosterImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcademicYearPreparationController extends Controller
{
    public function destroy(
        Request $request,
        AcademicYear $academicYear,
        AcademicYearPreparationService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $service->cancelPreparationYear($academicYear, $actor);

        return redirect()
            ->route('data-master.index')
            ->with('success', 'Persiapan tahun ajaran berhasil dibatalkan.');
    }

    public function previewApiSiswa(
        PreviewApiSiswaRosterRequest $request,
        ApiSiswaRosterImportService $service,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()
            ->json([
                'success' => true,
                'data' => $service->preview($request->apiUrl(), $actor),
            ])
            ->header('Cache-Control', 'no-store, private');
    }

    public function store(
        StoreProvisionalAcademicYearRequest $request,
        AcademicYearPreparationService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $service->prepareAcademicYear($request->validated(), $actor);

        return redirect()
            ->route('data-master.index')
            ->with('success', 'Tahun ajaran sementara berhasil dibuat. Selanjutnya impor daftar murid.');
    }

    public function storeRoster(
        ImportProvisionalRosterRequest $request,
        AcademicYear $academicYear,
        AcademicYearPreparationService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $result = $service->importRoster($academicYear, $request->rosterFile(), $actor);

        return redirect()
            ->route('data-master.index')
            ->with(
                'success',
                sprintf(
                    'Daftar berhasil diproses: %d baris, %d murid baru, dan %d murid yang sudah ada.',
                    $result->rows,
                    $result->studentsCreated,
                    $result->studentsMatched,
                ),
            );
    }

    public function storeGlobalRoster(
        ImportProvisionalRosterRequest $request,
        AcademicYearPreparationService $service,
        ApiSiswaRosterImportService $apiSiswaService,
    ): JsonResponse|RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $result = match (true) {
            $request->hasApiUrl() => $apiSiswaService->import($request->apiUrl(), $actor),
            $request->hasRosterPayload() => $service->importRosterPayload($request->rosterPayload(), $actor),
            default => $service->importRosters($request->rosterFile(), $actor),
        };

        $message = sprintf(
            'Daftar berhasil diproses: %d baris pada %d tahun pelajaran, %d murid baru, dan %d murid yang sudah ada.',
            $result->rows,
            $result->academicYears,
            $result->studentsCreated,
            $result->studentsMatched,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'rows' => $result->rows,
                    'academic_years' => $result->academicYears,
                    'students_created' => $result->studentsCreated,
                    'students_matched' => $result->studentsMatched,
                    'classrooms_created' => $result->classroomsCreated,
                    'memberships_created' => $result->membershipsCreated,
                    'memberships_unchanged' => $result->membershipsUnchanged,
                ],
            ]);
        }

        return redirect()
            ->route('data-master.index')
            ->with('success', $message);
    }
}
