<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FinalizeStudentDepartureRequest;
use App\Http\Requests\StoreStudentDepartureRequest;
use App\Http\Requests\UpdateStudentDepartureRequest;
use App\Models\Student;
use App\Models\StudentDeparture;
use App\Models\User;
use App\Services\StudentDepartureService;
use Illuminate\Http\RedirectResponse;

final class StudentDepartureController extends Controller
{
    public function store(StoreStudentDepartureRequest $request, Student $student, StudentDepartureService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $service->record($student, $request->validated(), $actor);

        return redirect()->route('students.show', $student)->with('success', 'Proses keluar murid berhasil dicatat.');
    }

    public function update(UpdateStudentDepartureRequest $request, Student $student, StudentDepartureService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        /** @var StudentDeparture $departure */
        $departure = $student->departure()->firstOrFail();
        $service->updateDraft($departure, $request->validated(), $actor);

        return redirect()->route('students.show', $student)->with('success', 'Proses keluar murid berhasil diperbarui.');
    }

    public function finalize(FinalizeStudentDepartureRequest $request, Student $student, StudentDepartureService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        /** @var StudentDeparture $departure */
        $departure = $student->departure()->firstOrFail();
        $service->finalize($departure, $request->validated(), $actor);

        return redirect()->route('students.show', $student)->with('success', 'Keputusan proses keluar murid berhasil disimpan.');
    }
}
