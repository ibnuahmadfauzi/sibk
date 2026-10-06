<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\BkCase;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class WakaDashboardService
{
    public function __construct(private readonly WakaCaseProjectionQuery $projection) {}

    /** @return array<string, mixed> */
    public function build(User $waka, ?AcademicYear $year): array
    {
        [$start, $end] = $this->period($year);
        $cases = $this->projection->build($waka, [
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
        ])->accessibleTo($waka)->with([
            'temporaryStudent:id,input_name,reconciled_student_id',
            'temporaryStudent.reconciledStudent:id,name',
            'classroom:id,name,grade_level',
        ])->get();
        $consultations = Consultation::query()->accessibleTo($waka)
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->select(['id', 'student_id', 'temporary_student_id', 'classroom_id', 'session_date'])
            ->with(['temporaryStudent:id,reconciled_student_id', 'classroom:id,name,grade_level'])
            ->get();
        $records = $this->records($cases, $consultations);
        $studentKeys = $records->pluck('student')->unique();
        $active = $cases->filter(static fn (BkCase $case): bool => ! ServiceRecordStatus::isTerminal($case->status?->code));
        $followUps = $cases->filter(static fn (BkCase $case): bool => $case->status?->code === ServiceRecordStatus::NEEDS_FOLLOW_UP);
        $firstDates = $this->firstServiceDates($waka);
        $month = CarbonImmutable::now()->format('Y-m');

        return [
            'role_key' => 'waka',
            'label' => 'Waka Kesiswaan',
            'user_name' => $waka->name,
            'scope' => $year?->name ?? 'Periode kalender berjalan',
            'read_only' => true,
            'metrics' => [
                ['label' => 'Murid', 'value' => (string) $studentKeys->count(), 'meta' => 'Memiliki Catatan', 'tone' => 'primary', 'kind' => 'students'],
                ['label' => 'Sedang Ditangani', 'value' => (string) $active->map(fn (BkCase $case): string => $this->studentKey($case))->unique()->count(), 'meta' => 'Masih aktif', 'tone' => 'info', 'kind' => 'cases'],
                ['label' => 'Perlu Tindak Lanjut', 'value' => (string) $followUps->map(fn (BkCase $case): string => $this->studentKey($case))->unique()->count(), 'meta' => 'Belum selesai', 'tone' => 'warning', 'kind' => 'schedule'],
                ['label' => 'Baru Bulan Ini', 'value' => (string) $studentKeys->filter(static fn (string $key): bool => str_starts_with($firstDates[$key] ?? '', $month) && ($firstDates[$key] ?? '') >= $start->toDateString() && ($firstDates[$key] ?? '') <= $end->toDateString())->count(), 'meta' => 'Bulan berjalan', 'tone' => 'success', 'kind' => 'students'],
            ],
            'trend' => $this->trend($records, $start, $end),
            'grades' => $this->grades($records),
            'top_case_classrooms' => $this->topCaseClassrooms($cases),
            'follow_up_students' => $this->followUpStudents($followUps),
        ];
    }

    /** @param Collection<int, BkCase> $cases @param Collection<int, Consultation> $consultations @return Collection<int, array{student: string, date: string, grade: string}> */
    private function records(Collection $cases, Collection $consultations): Collection
    {
        return $cases->map(fn (BkCase $case): array => [
            'student' => $this->studentKey($case),
            'date' => $case->service_date->toDateString(),
            'grade' => $this->grade($case->classroom),
            'order' => '0:'.sprintf('%020d', $case->getKey()),
        ])->concat($consultations->map(fn (Consultation $consultation): array => [
            'student' => $this->studentKey($consultation),
            'date' => $consultation->session_date->toDateString(),
            'grade' => $this->grade($consultation->classroom),
            'order' => '1:'.sprintf('%020d', $consultation->getKey()),
        ]))->values();
    }

    private function studentKey(BkCase|Consultation $record): string
    {
        $studentId = $record->student_id ?? $record->temporaryStudent?->reconciled_student_id;

        return $studentId !== null ? 's:'.$studentId : 't:'.$record->temporary_student_id;
    }

    /** @return array<string, string> */
    private function firstServiceDates(User $waka): array
    {
        $cases = BkCase::query()->accessibleTo($waka)
            ->select(['id', 'student_id', 'temporary_student_id', 'service_date'])
            ->with('temporaryStudent:id,reconciled_student_id')->get();
        $consultations = Consultation::query()->accessibleTo($waka)
            ->select(['id', 'student_id', 'temporary_student_id', 'session_date'])
            ->with('temporaryStudent:id,reconciled_student_id')->get();
        $first = [];
        foreach ($cases->concat($consultations) as $record) {
            $key = $this->studentKey($record);
            $date = ($record instanceof BkCase ? $record->service_date : $record->session_date)->toDateString();
            if (! isset($first[$key]) || $date < $first[$key]) {
                $first[$key] = $date;
            }
        }

        return $first;
    }

    /** @param Collection<int, array{student: string, date: string, grade: string}> $records @return list<array{label: string, month: string, count: int}> */
    private function trend(Collection $records, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $counts = $records->groupBy(static fn (array $record): string => substr($record['date'], 0, 7))
            ->map(static fn (Collection $month): int => $month->pluck('student')->unique()->count());
        $months = [];
        for ($cursor = $start->startOfMonth(); $cursor <= $end; $cursor = $cursor->addMonth()) {
            $key = $cursor->format('Y-m');
            $months[] = ['label' => $cursor->locale('id')->translatedFormat('M'), 'month' => $key, 'count' => $counts[$key] ?? 0];
        }

        return $months;
    }

    /** @param Collection<int, array{student: string, date: string, grade: string}> $records @return list<array{label: string, count: int}> */
    private function grades(Collection $records): array
    {
        $latest = $records->sortBy(static fn (array $record): string => $record['date'].':'.$record['order'])
            ->groupBy('student')->map(static fn (Collection $student): string => $student->last()['grade']);
        $counts = $latest->countBy();

        return collect(['X', 'XI', 'XII'])
            ->map(static fn (string $label): array => ['label' => $label, 'count' => $counts[$label] ?? 0])->all();
    }

    /** @param Collection<int, BkCase> $cases @return list<array{label: string, count: int}> */
    private function topCaseClassrooms(Collection $cases): array
    {
        return $cases->filter(static fn (BkCase $case): bool => $case->classroom !== null)
            ->groupBy('classroom_id')
            ->map(fn (Collection $classCases): array => [
                'label' => $classCases->first()->classroom->name,
                'count' => $classCases->map(fn (BkCase $case): string => $this->studentKey($case))->unique()->count(),
            ])
            ->sortBy([['count', 'desc'], ['label', 'asc']])
            ->take(3)->values()->all();
    }

    private function grade(?Classroom $classroom): string
    {
        $grade = $classroom?->grade_level;
        if (in_array($grade, [10, 11, 12], true)) {
            return [10 => 'X', 11 => 'XI', 12 => 'XII'][$grade];
        }

        if (preg_match('/^(12|11|10|XII|XI|X)(?:\s|$)/i', $classroom?->name ?? '', $match) === 1) {
            return ['10' => 'X', '11' => 'XI', '12' => 'XII'][strtoupper($match[1])] ?? strtoupper($match[1]);
        }

        return 'Tidak diketahui';
    }

    /** @param Collection<int, BkCase> $cases @return list<array<string, mixed>> */
    private function followUpStudents(Collection $cases): array
    {
        $summaries = BkCase::query()->whereKey($cases->modelKeys())->pluck('resolution_summary', 'id');

        return $cases->sortByDesc('service_date')->groupBy(fn (BkCase $case): string => $this->studentKey($case))
            ->map(function (Collection $studentCases) use ($summaries): array {
                /** @var BkCase $latest */
                $latest = $studentCases->first();

                return [
                    'name' => \App\Support\StudentName::display($latest->student?->name ?? $latest->temporaryStudent?->reconciledStudent?->name ?? $latest->identityName()),
                    'classroom' => $latest->classroom?->name ?? '-',
                    'services' => $studentCases->map(static fn (BkCase $case): array => [
                        'service' => 'Permasalahan',
                        'teacher' => $case->assignments->first()?->teacher?->name ?? '-',
                        'summary' => $summaries[$case->getKey()] ?: '-',
                        'follow_up' => $case->followUpType?->label ?? '-',
                    ])->values()->all(),
                ];
            })->values()->all();
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function period(?AcademicYear $year): array
    {
        return [
            $year?->starts_on !== null ? CarbonImmutable::instance($year->starts_on)->startOfDay() : CarbonImmutable::now()->startOfYear(),
            $year?->ends_on !== null ? CarbonImmutable::instance($year->ends_on)->endOfDay() : CarbonImmutable::now()->endOfYear(),
        ];
    }
}
