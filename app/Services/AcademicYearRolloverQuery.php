<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassMembership;
use Illuminate\Database\Eloquent\Builder;

final class AcademicYearRolloverQuery
{
    public function summarize(AcademicYear $targetYear): AcademicYearRolloverSummary
    {
        $sourceYear = $targetYear->starts_on === null
            ? null
            : AcademicYear::query()
                ->whereKeyNot($targetYear->getKey())
                ->whereDate('starts_on', '<', $targetYear->starts_on->toDateString())
                ->orderByDesc('starts_on')
                ->orderByDesc('id')
                ->first();

        if ($sourceYear === null) {
            return new AcademicYearRolloverSummary(
                targetYearId: (int) $targetYear->getKey(),
                sourceYearId: null,
                sourceYearName: null,
                needsConfirmation: [],
            );
        }

        $students = Student::query()
            ->active()
            ->whereHas('classMemberships', fn (Builder $memberships): Builder => $memberships
                ->active()
                ->where('academic_year_id', $sourceYear->getKey()))
            ->whereDoesntHave('classMemberships', fn (Builder $memberships): Builder => $memberships
                ->active()
                ->where('academic_year_id', $targetYear->getKey()))
            ->with(['classMemberships' => fn ($memberships) => $memberships
                ->active()
                ->where('academic_year_id', $sourceYear->getKey())
                ->with('classroom')
                ->orderBy('id')])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $needsConfirmation = $students->map(function (Student $student): array {
            /** @var StudentClassMembership $membership */
            $membership = $student->classMemberships->first();

            return [
                'student_id' => (int) $student->getKey(),
                'nisn' => $student->nisn,
                'student_name' => $student->name,
                'source_classroom' => $membership->classroom?->name ?? '-',
            ];
        })->all();

        return new AcademicYearRolloverSummary(
            targetYearId: (int) $targetYear->getKey(),
            sourceYearId: (int) $sourceYear->getKey(),
            sourceYearName: $sourceYear->name,
            needsConfirmation: $needsConfirmation,
        );
    }
}
