<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\AcademicYearPreparationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AcademicYearActivationController extends Controller
{
    public function restorePrevious(
        Request $request,
        AcademicYear $academicYear,
        AcademicYearPreparationService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        Gate::forUser($actor)->authorize('create', TeacherAssignment::class);
        $previous = $service->restorePreviousAcademicYear($academicYear, $actor);

        return redirect()
            ->route('assignments.classes.index', ['academic_year_id' => $previous->getKey()])
            ->with('success_title', 'Tahun ajaran dikembalikan')
            ->with('success', sprintf('%s kembali menjadi tahun ajaran aktif.', $previous->name));
    }

    public function store(
        Request $request,
        AcademicYear $academicYear,
        AcademicYearPreparationService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        Gate::forUser($actor)->authorize('create', TeacherAssignment::class);
        $service->activate($academicYear, $actor);

        return redirect()
            ->route('assignments.classes.manage', ['academic_year_id' => $academicYear->getKey()])
            ->with('success', 'Tahun ajaran berhasil diaktifkan. Guru BK kini dapat melayani murid sesuai kelasnya.');
    }
}
