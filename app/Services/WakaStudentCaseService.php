<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Requests\WakaMonitoringRequest;
use App\Models\BkCase;
use App\Models\User;
use App\Support\ServiceRecordStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class WakaStudentCaseService
{
    public function __construct(
        private readonly WakaCaseProjectionQuery $projection,
        private readonly WakaMonitoringService $monitoring,
    ) {}

    /**
     * @param  array<string, string|null>  $filters
     * @return LengthAwarePaginator<array<string, mixed>>
     */
    public function paginateSafe(User $waka, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'murid';
        $direction = $filters['direction'] ?? 'asc';
        if (! in_array($sort, WakaMonitoringRequest::STUDENT_SORT_ALLOWLIST, true)) {
            throw new InvalidArgumentException('Pilihan urutan daftar murid Waka tidak valid.');
        }
        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Arah urutan daftar murid Waka tidak valid.');
        }

        $queryFilters = $filters;
        $queryFilters['sort'] = 'tanggal';
        $queryFilters['direction'] = 'desc';
        unset($queryFilters['page']);

        $rows = $this->projection->build($waka, $queryFilters)
            ->get()
            ->groupBy(static fn (BkCase $case): string => $case->student_id !== null
                ? 'student:'.$case->student_id
                : 'temporary:'.$case->temporary_student_id)
            ->map(fn (Collection $cases, string $identityKey): array => $this->toStudentRow($cases, $identityKey));

        $sortColumn = match ($sort) {
            'kelas' => 'kelas',
            'status' => 'status_terbaru',
            'guru_bk' => 'guru_bk',
            default => 'nama_murid',
        };
        $rows = $rows->sort(function (array $left, array $right) use ($sortColumn, $direction): int {
            $comparison = strnatcasecmp((string) $left[$sortColumn], (string) $right[$sortColumn]);
            if ($comparison === 0) {
                $comparison = strcmp($left['_identity_key'], $right['_identity_key']);
            }

            return $direction === 'desc' ? -$comparison : $comparison;
        })->map(static function (array $row): array {
            unset($row['_identity_key']);

            return $row;
        })->values();

        $page = max(1, (int) ($filters['page'] ?? 1));
        $items = $rows->forPage($page, $perPage)->values()->all();

        return new LengthAwarePaginator($items, $rows->count(), $perPage, $page, [
            'path' => route('waka.monitoring.students'),
            'pageName' => 'page',
        ]);
    }

    /**
     * @param  Collection<int, BkCase>  $cases
     * @return array<string, mixed>
     */
    private function toStudentRow(Collection $cases, string $identityKey): array
    {
        $ordered = $cases->sortByDesc(static fn (BkCase $case): string => sprintf(
            '%s-%020d',
            $case->service_date?->format('Y-m-d') ?? '',
            $case->getKey(),
        ))->values();
        /** @var BkCase $latest */
        $latest = $ordered->first();
        $latestSafe = $this->monitoring->toSafeRow($latest);
        $active = $ordered->filter(static fn (BkCase $case): bool => ! ServiceRecordStatus::isTerminal($case->status?->code));
        $ownerCase = $active->first() ?? $latest;
        $ownerSafe = $this->monitoring->toSafeRow($ownerCase);
        $coordinationUrl = $ordered
            ->map(fn (BkCase $case): ?string => $this->monitoring->toSafeRow($case)['coordination_url'])
            ->first(static fn (?string $url): bool => $url !== null);

        return [
            '_identity_key' => $identityKey,
            'nama_murid' => $latestSafe['nama_murid'],
            'kelas' => $latestSafe['kelas'],
            'jumlah_kasus' => $ordered->count(),
            'jumlah_aktif' => $active->count(),
            'status_terbaru' => $latestSafe['status'],
            'status_code' => $latestSafe['status_code'],
            'guru_bk' => $ownerSafe['guru_bk'],
            'coordination_url' => $coordinationUrl,
        ];
    }
}
