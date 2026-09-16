<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportProvisionalRosterRequest;
use App\Http\Requests\Admin\StoreProvisionalAcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
use Illuminate\Http\RedirectResponse;

class AcademicYearPreparationController extends Controller
{
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
}
