<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\StudentDeparture;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class WakaStudentDepartureService
{
    /** @return LengthAwarePaginator<StudentDeparture> */
    public function paginate(int $perPage = 20, string $sort = '', string $direction = ''): LengthAwarePaginator
    {
        return StudentDeparture::query()
            ->with([
                'student.classMemberships' => fn ($memberships) => $memberships
                    ->with('classroom')
                    ->orderByDesc('academic_year_id')
                    ->orderByDesc('id'),
                'recorder:id,name',
                'finalizer:id,name',
            ])
            ->when(in_array($sort, ['murid', 'tanggal', 'efektif'], true) && in_array($direction, ['asc', 'desc'], true),
                fn ($query) => $query->orderBy(match ($sort) {
                    'murid' => \App\Models\Student::query()->select('name')->whereColumn('students.id', 'student_departures.student_id')->limit(1),
                    'efektif' => 'effective_date',
                    default => 'reported_at',
                }, $direction),
                fn ($query) => $query->latest('reported_at'))
            ->orderBy('student_departures.id', $direction === 'asc' ? 'asc' : 'desc')
            ->paginate($perPage);
    }

    public function auditViewed(User $actor, int $resultCount, Request $request): void
    {
        AuditLog::query()->create([
            'actor_id' => $actor->getKey(),
            'action' => 'waka.student_departures.viewed',
            'auditable_type' => 'waka_student_departures',
            'auditable_id' => $actor->getKey(),
            'summary' => sprintf('Waka membaca %d proses keluar murid.', $resultCount),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
